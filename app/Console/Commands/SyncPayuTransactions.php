<?php

namespace App\Console\Commands;

use App\Services\PayuTransactionSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncPayuTransactions extends Command
{
    protected $signature = 'payu:sync {transaction? : Recheck a particular merchant transaction ID} {--all : Recheck historical final results too}';
    protected $description = 'Recover PayU payment details and update existing transaction rows';

    public function handle(PayuTransactionSync $sync)
    {
        $id = $this->argument('transaction');
        DB::table('payment_details')->where('pg_name', 'payu')->whereNotNull('payu_txnid')
            ->where('payu_txnid', '<>', '')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')->from('payu_transactions')->whereColumn('payu_transactions.transaction_id', 'payment_details.payu_txnid');
            })
            ->when($id, fn ($query) => $query->where('payu_txnid', $id))
            ->orderBy('id')->chunkById(100, function ($details) use ($sync) {
                foreach ($details as $detail) {
                    $sync->importMobile($detail->payu_txnid);
                }
            });

        $failed = 0;
        if ($id && !DB::table('payu_transactions')->where('transaction_id', $id)->exists()) {
            try {
                $sync->reconcile($id);
                $this->info('PayU transaction verified and imported. Check Admin > Payments.');
                return 0;
            } catch (\Throwable $exception) {
                $this->error($exception->getMessage());
                return 1;
            }
        }
        DB::table('payu_transactions')->where('destination', 'like', '%/member/payment/status')
            ->when($id, fn ($query) => $query->where('transaction_id', $id), function ($query) {
                if (!$this->option('all')) {
                    $query->whereIn('status', ['pending', 'invalid'])->where('created_at', '<', now()->subMinutes(2));
                }
            })
            ->orderBy('id')->chunkById(100, function ($rows) use ($sync, &$failed) {
                foreach ($rows as $row) {
                    try {
                        $sync->reconcile($row->transaction_id);
                    } catch (\Throwable $exception) {
                        $failed++;
                        Log::warning('PayU reconciliation deferred', ['transaction_id' => $row->transaction_id, 'error' => $exception->getMessage()]);
                        $this->warn($row->transaction_id.': '.$exception->getMessage());
                    }
                }
            });

        return $failed ? 1 : 0;
    }
}
