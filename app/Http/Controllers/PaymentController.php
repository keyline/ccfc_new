<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentToken;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function showPaymentPage($token)
    {
        $hashedToken = hash('sha256', $token);
        $paymentToken = PaymentToken::where('token', $hashedToken)->first();

        if (!$paymentToken || $paymentToken->status !== 'active' || $paymentToken->expires_at < now()) {
            abort(404);
        }

        // Mark the token as accessed
        $paymentToken->increment('access_count');
        $paymentToken->update(['last_accessed_at' => now(), 'ip_address' => request()->ip()]);

        $due = $paymentToken->memberDue;

        return view('payment.page', compact('due', 'paymentToken'));
    }
}
