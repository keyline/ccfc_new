<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class MobilePayuClubmanPosting
{
    public function post(string $memberCode, string $transactionId, float $amount): void
    {
        if (trim($transactionId) === '' || $amount <= 0) {
            throw new RuntimeException('Invalid mobile PayU payment.');
        }

        DB::table('mobile_payu_clubman_postings')->insertOrIgnore([
            'transaction_id' => $transactionId,
            'member_code' => $memberCode,
            'amount' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::transaction(function () use ($memberCode, $transactionId, $amount) {
            $posting = DB::table('mobile_payu_clubman_postings')
                ->where('transaction_id', $transactionId)->lockForUpdate()->first();

            if ($posting->member_code !== $memberCode || round((float) $posting->amount, 2) !== round($amount, 2)) {
                throw new RuntimeException('Mobile PayU payment does not match the stored posting.');
            }

            if ($posting->posted_at !== null) {
                return;
            }

            app(ClubmanPaymentPosting::class)->post(
                $memberCode, $transactionId, $amount, $transactionId,
                'PayU payment against outstanding', 'PayU'
            );

            DB::table('mobile_payu_clubman_postings')->where('transaction_id', $transactionId)
                ->update(['posted_at' => now(), 'updated_at' => now()]);
        });
    }
}
