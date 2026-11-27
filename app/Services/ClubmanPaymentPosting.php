<?php

namespace App\Services;

use App\Models\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ClubmanPaymentPosting
{
    public function retry(\Tzsk\Payu\Models\PayuTransaction $payment, string $memberCode): array
    {
        if ($payment->clubman_status !== 'failure' || ! $payment->successful()) {
            throw new RuntimeException('Only successful payments with a failed Clubman posting can be retried.');
        }

        $gateway = basename(parse_url($payment->destination, PHP_URL_PATH));
        $amount = $payment->response('amount');
        switch ($gateway) {
            case 'status':
                if ($payment->payment_source === 'mobile_app') {
                    try {
                        app(MobilePayuClubmanPosting::class)->post($memberCode, $payment->transaction_id, (float) $amount);
                    } catch (Throwable $exception) {
                        $this->recordResult($payment->transaction_id, 'failure');
                        throw $exception;
                    }
                    $this->recordResult($payment->transaction_id, 'success');
                    return ['Status' => 'success'];
                }
                $reference = $payment->response('mihpayid') ?: $payment->response('id');
                $gatewayName = 'PayU';
                break;
            case 'axisstatus':
            case 'razorpaycallback':
                $reference = $payment->response('id');
                $amount = is_numeric($amount) ? $amount / 100 : null;
                $gatewayName = $gateway === 'axisstatus' ? 'AXIS Razor Pay' : 'Razor Pay';
                break;
            case 'hdfcstatus':
                $amount = $payment->response('txn_amount') ?? $amount;
                $reference = $payment->response('txn_id') ?: $payment->response('transactionid') ?: $payment->response('mihpayid');
                $gatewayName = 'HDFC VAS';
                break;
            case 'hdfcsmartpaycallback':
                $reference = $payment->response('orderId');
                $gatewayName = 'HDFC SMART Pay';
                break;
            default:
                throw new RuntimeException('This payment gateway does not support Clubman retry.');
        }

        if (! is_numeric($amount) || $amount <= 0 || ! is_scalar($reference) || trim((string) $reference) === '') {
            throw new RuntimeException('The stored payment amount or reference is missing. Clubman retry was not sent.');
        }

        return $this->postAndRecord(
            $payment->transaction_id, $memberCode, (string) $reference,
            (float) $amount, (string) $reference,
            'Retry payment against outstanding', $gatewayName
        );
    }

    public function postAndRecord(
        string $transactionId,
        string $memberCode,
        string $voucherNo,
        float $amount,
        string $instrumentNo,
        string $description = 'Online payment against outstanding',
        string $paymentGateway = ''
    ): array {
        try {
            if ($paymentGateway === 'PayU') {
                return DB::transaction(function () use ($transactionId, $memberCode, $voucherNo, $amount, $instrumentNo, $description, $paymentGateway) {
                    $payment = DB::table('payu_transactions')->where('transaction_id', $transactionId)->lockForUpdate()->first();
                    if ($payment && $payment->clubman_status === 'success') {
                        return ['Status' => 'success'];
                    }
                    $result = $this->post($memberCode, $voucherNo, $amount, $instrumentNo, $description, $paymentGateway);
                    $this->recordResult($transactionId, 'success');
                    return $result;
                });
            }
            $result = $this->post($memberCode, $voucherNo, $amount, $instrumentNo, $description, $paymentGateway);
        } catch (Throwable $exception) {
            $this->recordResult($transactionId, 'failure');
            throw $exception;
        }

        $this->recordResult($transactionId, 'success');

        return $result;
    }

    private function recordResult(string $transactionId, string $status): void
    {
        DB::table('payu_transactions')->where('transaction_id', $transactionId)
            ->when($status === 'failure', function ($query) {
                $query->where(function ($query) {
                    $query->whereNull('clubman_status')->orWhere('clubman_status', '<>', 'success');
                });
            })->update([
            'clubman_status' => $status,
            'clubman_checked_at' => Carbon::now('Asia/Kolkata'),
        ]);
    }

    public function post(
        string $memberCode,
        string $voucherNo,
        float $amount,
        string $instrumentNo,
        string $description = 'Online payment against outstanding',
        string $paymentGateway = ''
    ): array {
        $memberCode = trim($memberCode);
        $paymentGateway = trim($paymentGateway) ?: 'Online Payment';

        if ($memberCode === '') {
            throw new RuntimeException('This member does not have a Clubman membership ID.');
        }

        if ($amount <= 0 || trim($voucherNo) === '' || trim($instrumentNo) === '') {
            throw new RuntimeException('The payment amount or reference is missing. Clubman posting was not sent.');
        }

        $endpoint = trim((string) config('services.clubman.payment_posting_url'));

        if ($endpoint === '') {
            throw new RuntimeException('The Clubman Payment Posting URL is not configured.');
        }

        $payload = [
            'VoucherNo' => $voucherNo,
            'MemberId' => $memberCode,
            'VoucherDate' => Carbon::now('Asia/Kolkata')->format('d M Y'),
            'Amount' => round($amount, 2),
            'InstrumentNo' => $instrumentNo,
            'paymentgateway' => $paymentGateway,
            'Description' => $description,
        ];

        try {
            $response = Http::withoutVerifying()
                ->acceptJson()
                ->withToken($this->apiToken())
                ->withHeaders(['Cache-Control' => 'no-cache'])
                ->timeout((int) config('services.clubman.timeout', 15))
                ->withOptions([
                    'connect_timeout' => (int) config('services.clubman.connect_timeout', 5),
                ])
                ->post($endpoint . '?' . http_build_query(['json' => json_encode($payload)]));
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Clubman could not be reached while posting the payment.',
                0,
                $exception
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'Clubman rejected the payment posting (HTTP ' . $response->status() . ').'
            );
        }

        $result = $response->json();

        if (! is_array($result)) {
            throw new RuntimeException('Clubman returned an invalid payment posting response.');
        }

        $status = strtolower(trim((string) ($result['Status'] ?? '')));

        if ($status !== 'success') {
            $message = trim((string) ($result['StatusMessage'] ?? ''));

            throw new RuntimeException($message ?: 'Clubman rejected the payment posting.');
        }

        return $result;
    }

    private function apiToken(): string
    {
        $settingToken = '';

        try {
            $setting = GeneralSetting::find(1);
            $settingToken = $setting ? trim((string) $setting->clubman_api_token) : '';
        } catch (Throwable $exception) {
            // The environment token remains available during setup or DB maintenance.
        }

        $token = $settingToken ?: trim((string) config('services.clubman.token'));

        if ($token === '') {
            throw new RuntimeException(
                'The Clubman API token is missing from Admin Settings and the environment.'
            );
        }

        return $token;
    }
}
