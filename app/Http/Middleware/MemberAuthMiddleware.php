<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
//use Auth;

use Illuminate\Support\Facades\Auth;

class MemberAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        /*if (!session()->has('LoggedMember') && $request->path() != 'member/login') {
            return redirect('/')->with('fail', 'Members must be logged in!');
        }

        if (session()->has('LoggedMember') && $request->path() == 'member/login') {
            return back();
        }

        if (session()->has('firstMemberUpdate') && $request->path() == 'member/updateme') {
            return $next($request);
        }*/

        // if (session()->has('firstMemberUpdate') && ! $request->is('member/*/update')) {
        //     return redirect()->route('member.profileupdate', session('firstMemberUpdate'));
        // }

        // if (session()->has('LoggedMember')
        //     &&
        //     ! (strtolower(Auth::user()->status) == 'active' || strtolower(Auth::user()->status) == 'inactive')) {
        //     return redirect('/')->with("fail", "YOUR MEMBERSHIP NO. " . Auth::user()->user_code . " STANDS TERMINATED.");
        // }


        $guard = Auth::guard('members');

        // -----------------------------------------
        // 1) If NOT logged in → redirect to login
        // -----------------------------------------
        if (! $guard->check()) {
            // Allow access ONLY to login page
            if ($request->is('member/login') || $request->is('member/magic-login/*')) {
                return $next($request);
            }

            return redirect('/')
                ->with('fail', 'Members must be logged in!');
        }

        $member = $guard->user();


        // ----------------------------------------------------
        // 2) If logged in and tries to visit login → send back
        // ----------------------------------------------------
        if ($guard->check() && $request->is('member/login')) {
            return redirect()->route('member.dashboard');
        }


        // ---------------------------------------------------------
        // 3) Force first-time update (if required by your logic)
        // ---------------------------------------------------------
        if ($member->first_update_required ?? false) {

            // allow access to update form
            if ($request->is('member/updateme')) {
                return $next($request);
            }

            // block all other pages → redirect to update profile
            return redirect()->route('member.profileupdate', $member->id);
        }


        // ----------------------------------------------------------
        // 4) Membership status verification (active | inactive ONLY)
        // ----------------------------------------------------------
        $status = strtolower($member->status);

        if (! in_array($status, ['active', 'inactive'])) {
            return redirect('/')
                ->with('fail', 'YOUR MEMBERSHIP NO. ' . $member->user_code . ' STANDS TERMINATED.');
        }


        // ----------------------------------------------------------
        // 5) Continue request + prevent caching
        // ----------------------------------------------------------


        return $next($request)->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
                                ->header('Pragma', 'no-cache')
                                ->header('Expires', 'Sat 01 Jan 1990 00:00:00 GMT');
    }
}
