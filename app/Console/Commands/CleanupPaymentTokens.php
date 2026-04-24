<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentToken;
use Illuminate\Support\Facades\Config;

class CleanupPaymentTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment-tokens:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old and expired payment tokens.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $retentionDays = Config::get('auth.payment_token_retention_days', 30);
        $cleanupDate = now()->subDays($retentionDays);

        // Clean up used, expired, and revoked tokens
        PaymentToken::where('used_at', '<', $cleanupDate)
            ->orWhere('expires_at', '<', $cleanupDate)
            ->delete();

        $this->info('Old payment tokens have been cleaned up.');
        return 0;
    }
}
