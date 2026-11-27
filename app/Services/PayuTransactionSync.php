<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tzsk\Payu\Checksum;
use Tzsk\Payu\Gateway\PayuBiz;
use Tzsk\Payu\Models\PayuTransaction;

class PayuTransactionSync
{
    public function pending(string $id, int $userId, $amount): void
    {
        if ($id === '' || strlen($id) > 255 || !is_numeric($amount) || $amount <= 0) {
            throw ValidationException::withMessages(['payment' => 'Invalid PayU transaction or amount.']);
        }

        DB::table('payu_transactions')->upsert([[
            'transaction_id' => $id, 'paid_for_id' => $userId, 'paid_for_type' => (new User())->getMorphClass(),
            'payment_source' => 'mobile_app', 'gateway' => 'PayU', 'body' => '', 'hash' => '',
            'destination' => url('/member/payment/status'), 'status' => 'pending',
            'response' => json_encode(['amount' => (string) $amount, 'status' => 'pending']),
            'created_at' => now(), 'updated_at' => now(),
        ]], ['transaction_id'], ['transaction_id']);
    }

    public function receive(array $data, ?int $userId = null): void
    {
        validator($data, [
            'txnid' => 'required|string|max:255', 'key' => 'required|string',
            'hash' => 'required|string|size:128', 'amount' => 'required|numeric|gt:0',
            'status' => 'required|in:success,failure,failed,pending',
        ])->validate();

        $gateway = collect(config('payu.gateways'))->push(new PayuBiz(config('payu_mobile')))->first(function ($gateway) use ($data) {
            return $gateway->key === $data['key'];
        });
        abort_unless($gateway, 403, 'Unknown PayU merchant.');
        $expected = Checksum::with($gateway->salt())->generate($data, true);
        if (isset($data['additionalCharges'])) {
            $sequence = [$data['additionalCharges'], $gateway->salt(), $data['status']];
            foreach (array_merge(range(10, 1), ['email', 'firstname', 'productinfo', 'amount', 'txnid', 'key']) as $field) {
                $sequence[] = $data[is_int($field) ? 'udf'.$field : $field] ?? '';
            }
            $expected = hash('sha512', implode('|', $sequence));
        }
        abort_unless(hash_equals($expected, strtolower($data['hash'])), 403, 'Invalid PayU response hash.');

        $this->importMobile($data['txnid']);
        $row = DB::table('payu_transactions')->where('transaction_id', $data['txnid'])->first();
        if (!$row && $userId !== null) {
            $user = User::findOrFail($userId);
            abort_unless(in_array((string) ($data['udf1'] ?? ''), [(string) $userId, (string) $user->user_code], true), 403, 'PayU member mapping does not match.');
            $this->pending($data['txnid'], $userId, $data['amount']);
        }
        $this->save($data['txnid'], $data, $userId);
    }

    public function importMobile(string $id): void
    {
        $detail = DB::table('payment_details')->where('payu_txnid', $id)->where('pg_name', 'payu')->first();
        if ($detail) {
            $user = User::where('user_code', $detail->membership_no)->first();
            if ($user) {
                $this->pending($id, $user->id, $detail->amount);
            }
        }
    }

    public function reconcile(string $id): void
    {
        validator(['transaction' => $id], ['transaction' => 'required|string|max:255'])->validate();
        $payment = PayuTransaction::withTrashed()->where('transaction_id', $id)->first();
        abort_if($payment && basename(parse_url($payment->destination, PHP_URL_PATH)) !== 'status', 422, 'Not a PayU payment.');
        $gateway = !$payment || $payment->payment_source === 'mobile_app' || $payment->getRawOriginal('gateway') === 'PayU'
            ? new PayuBiz(config('payu_mobile')) : $payment->gateway;
        if (!$gateway->key || !$gateway->salt()) {
            throw new \RuntimeException('PayU merchant key and salt are not configured.');
        }
        $host = $gateway->mode === 'test' ? 'test' : 'info';
        $data = Http::asForm()->timeout(20)->post("https://{$host}.payu.in/merchant/postservice.php?form=2", [
            'key' => $gateway->key, 'command' => 'verify_payment', 'var1' => $id,
            'hash' => hash('sha512', $gateway->key.'|verify_payment|'.$id.'|'.$gateway->salt()),
        ])->throw()->json();
        $response = $data['transaction_details'][$id] ?? null;
        if (($data['status'] ?? 0) != 1 || !is_array($response) || !in_array($response['status'] ?? '', ['success', 'failure', 'failed', 'pending'], true)) {
            throw new \RuntimeException('PayU has not returned a transaction result.');
        }
        $response['amount'] = $response['amt'] ?? $response['amount'] ?? null;
        abort_if(isset($response['txnid']) && $response['txnid'] !== $id, 422, 'PayU transaction ID mismatch.');
        $response['txnid'] = $id;
        $userId = null;
        if (!$payment) {
            // Match only member codes returned by PayU; never infer ownership from the transaction ID.
            $codes = array_filter([$response['udf1'] ?? null, $response['productinfo'] ?? null], 'is_string');
            $users = User::whereIn('user_code', $codes)->get();
            if ($users->count() !== 1) {
                throw new \RuntimeException('PayU verified the transaction, but its member code is missing, unknown or ambiguous. No payment was imported.');
            }
            $userId = $users->first()->id;
            $this->pending($id, $userId, $response['amount']);
        }
        $this->save($id, $response, $userId);
    }

    private function save(string $id, array $data, ?int $userId = null): void
    {
        DB::transaction(function () use ($id, $data, $userId) {
            $row = DB::table('payu_transactions')->where('transaction_id', $id)->lockForUpdate()->first();
            abort_unless($row && $row->paid_for_type === (new User())->getMorphClass(), 422, 'PayU member mapping is missing.');
            abort_if($userId !== null && (int) $row->paid_for_id !== $userId, 403, 'Transaction belongs to another member.');
            abort_unless(basename(parse_url($row->destination, PHP_URL_PATH)) === 'status', 422, 'Not a PayU payment.');
            $previous = json_decode($row->response ?? '', true) ?: [];
            $expectedAmount = $previous['amount'] ?? null;
            abort_if(!is_numeric($data['amount'] ?? null) || $data['amount'] <= 0, 422, 'Invalid PayU amount.');
            abort_if($expectedAmount !== null && round((float) $expectedAmount, 2) !== round((float) $data['amount'], 2), 422, 'PayU amount mismatch.');
            // A delayed failure must not overwrite an already confirmed payment.
            if ($row->status === 'successful' && $data['status'] !== 'success') {
                return;
            }
            DB::table('payu_transactions')->where('id', $row->id)->update([
                'response' => json_encode(array_replace($previous, $data)),
                'status' => $data['status'] === 'success' ? 'successful' : ($data['status'] === 'pending' ? 'pending' : 'failed'),
                'hash' => $data['hash'] ?? $row->hash, 'updated_at' => now(), 'deleted_at' => null,
            ]);
        });

        $this->postConfirmed($id);
    }

    private function postConfirmed(string $id): void
    {
        $row = DB::table('payu_transactions')->where('transaction_id', $id)->first();
        if ($row->status !== 'successful' || $row->clubman_status === 'success') {
            return;
        }
        try {
            $user = User::findOrFail($row->paid_for_id);
            $response = json_decode($row->response, true);
            if ($row->payment_source === 'mobile_app') {
                app(MobilePayuClubmanPosting::class)->post($user->user_code, $id, (float) $response['amount']);
            } else {
                $reference = $response['mihpayid'] ?? $response['id'] ?? '';
                if (!$reference) {
                    throw new \RuntimeException('PayU reference is missing.');
                }
                app(ClubmanPaymentPosting::class)->postAndRecord($id, $user->user_code, (string) $reference, (float) $response['amount'], (string) $reference, 'PayU payment against outstanding', 'PayU');
            }
            DB::table('payu_transactions')->where('id', $row->id)->update(['clubman_status' => 'success', 'clubman_checked_at' => now()]);
        } catch (\Throwable $exception) {
            DB::table('payu_transactions')->where('id', $row->id)->where(function ($query) {
                $query->whereNull('clubman_status')->orWhere('clubman_status', '<>', 'success');
            })->update(['clubman_status' => 'failure', 'clubman_checked_at' => now()]);
            Log::error('PayU Clubman posting failed', ['transaction_id' => $id, 'error' => $exception->getMessage()]);
        }
    }
}
