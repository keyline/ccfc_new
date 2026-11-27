<?php

// Save verified responses even when the app never sends its separate response request.
$payuSynced = false;
try {
    require_once dirname(__DIR__, 2).'/vendor/autoload.php';
    $application = require dirname(__DIR__, 2).'/bootstrap/app.php';
    $application->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $application->make(\App\Services\PayuTransactionSync::class)->receive($_POST);
    $payuSynced = \Illuminate\Support\Facades\DB::table('payu_transactions')
        ->where('transaction_id', $_POST['txnid'])->value('clubman_status') === 'success';
} catch (\Throwable $exception) {
    error_log('PayU response sync failed: '.$exception->getMessage());
}
