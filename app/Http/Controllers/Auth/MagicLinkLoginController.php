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
        $hashed = hash('sha256', $token);

        $paymentToken = PaymentToken::with('member')->where('token', $hashed)->firstOrFail();


        $user = $paymentToken->member;


        if (! $user instanceof \App\Models\User) {
            //return redirect()->route('/')->with('error', 'Invalid user.');

            return redirect('/')
                ->with('error', 'Invalid or expired token.');

        }



        if (!$paymentToken || $paymentToken->isExpired()) {
            // Here you can redirect to a specific error page
            //return redirect()->route('/')->with('error', 'Invalid or expired token.');

            return redirect('/')
                ->with('error', 'Invalid or expired token.');

        }

        // Log the member in
        Auth::guard('members')->login($user);

        //hack to set session LoggedMember

        $request->session()->put('LoggedMember', ['id' => $user->id, 'name' => $user->name]);

        $request->session()->put('tokenPayment', ['active_id' => $paymentToken->id, 'due_id' => $paymentToken->member_due_id]);

        // Mark the token as used
        $paymentToken->markAsUsed($request->ip(), $request->userAgent());

        // Redirect to the intended page, e.g., the member's dashboard
        return redirect()->intended('member/invoice');
    }
}
