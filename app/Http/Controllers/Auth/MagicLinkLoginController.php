
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PaymentToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MagicLinkLoginController extends Controller
{
    public function login(Request $request, $token)
    {
        $paymentToken = PaymentToken::where('token', $token)->first();

        if (!$paymentToken || $paymentToken->isUsed() || $paymentToken->isExpired()) {
            // Here you can redirect to a specific error page
            return redirect()->route('login')->with('error', 'Invalid or expired token.');
        }

        // Log the member in
        Auth::guard('members')->login($paymentToken->member);

        // Mark the token as used
        $paymentToken->markAsUsed($request->ip(), $request->userAgent());

        // Redirect to the intended page, e.g., the member's dashboard
        return redirect()->intended('/dashboard');
    }
}
