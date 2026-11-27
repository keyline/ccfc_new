<?php

namespace App\Console\Commands;

use App\Services\PayuTransactionSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncPayuTransactions extends Command
{
    protected $signature = 'payu:sync {transaction? : Recheck a particular merchant transaction ID} {--all : Recheck historical final results too} {--date= : Fetch YYYY-MM-DD or yesterday from PayU} {--gateway= : PayU account: mobile, money or biz}';
    protected $description = 'Recover PayU payment details and update existing transaction rows';

    public function handle(PayuTransactionSync $sync)
    {
        $id = $this->argument('transaction');
        if ($this->option('date')) {
            if ($id || $this->option('all')) {
                $this->error('Use --date separately from a transaction ID or --all.');
                return 1;
            }
            $date = $this->option('date') === 'yesterday'
                ? \Carbon\Carbon::now('Asia/Kolkata')->subDay()->toDateString() : $this->option('date');
            $gateway = $this->option('gateway') ?: config('payu.default', 'money');
            $saved = $failed = 0;
            $this->info('Fetching PayU transactions for '.$date.' using '.$gateway.'.');
            try {
                foreach ($sync->transactionsForDate($date, $gateway) as $transactionId) {
                    try {
                        $sync->reconcile($transactionId, $gateway);
                        $saved++;
                        $row = DB::table('payu_transactions')->where('transaction_id', $transactionId)->first();
                        if ($row->status === 'successful' && $row->clubman_status !== 'success') {
                            $failed++;
                            $this->warn($transactionId.': payment saved; Clubman posting needs retry.');
                        }
                    } catch (\Throwable $exception) {
                        $failed++;
                        $this->warn($transactionId.': '.$exception->getMessage());
                    }
                }
            } catch (\Throwable $exception) {
                $failed++;
                $this->error($exception->getMessage());
            }
            $this->info('Saved/updated: '.$saved.'. Errors or Clubman retries: '.$failed.'.');
            return $failed ? 1 : 0;
        }
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
                $sync->reconcile($id, $this->option('gateway'));
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
                        $sync->reconcile($row->transaction_id, $this->option('gateway'));
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
