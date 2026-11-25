<?php

namespace App\Services;

use Illuminate\Http\Request;

class PaymentSource
{
    public static function fromRequest(Request $request): string
    {
        // Require the quick-access session as well as the originating form marker.
        // A stale session flag alone must not classify a regular invoice payment.
        if ($request->input('payment_source') === 'quick_access'
            && $request->hasSession()
            && $request->session()->get('quickaccess.payment_in_progress')) {
            return 'quick_access';
        }

        if ($request->hasSession() && $request->session()->get('tokenPayment.active_id')) {
            return 'payment_link';
        }

        return 'member_login';
    }

    public static function label(?string $source): string
    {
        return [
            'quick_access' => 'Quick Access',
            'member_login' => 'Member Login',
            'payment_link' => 'Payment Link',
        ][$source] ?? '-';
    }
}
