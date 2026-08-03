<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ClubmanMemberLookup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuickAccessController extends Controller
{
    public function showMemberNumberForm()
    {
        return view('member.quick_access.member_number');
    }

    public function checkMember(Request $request)
    {
        $request->validate([
            'member_code' => 'required|string',
        ]);

        $user = User::where('user_code', $request->member_code)->first();

        if (!$user) {
            return back()->withErrors(['member_code' => 'Member number not found! please contact admin']);
        }

        $status = strtolower(trim((string) $user->status));

        if (!in_array($status, ['active', 'inactive'], true)) {
            return back()->with('quickaccess_blocked', [
                'message' => 'YOUR MEMBERSHIP NO. ' . $user->user_code . ' IS ' . strtoupper($status ?: 'NOT ACTIVE') . '.',
            ]);
        }

        $request->session()->put('quickaccess.pending_user_id', $user->id);

        return back()->with('quickaccess_confirm', [
            'name' => $user->name,
            'member_code' => $user->user_code,
        ]);
    }

    public function confirmAndPay(Request $request)
    {
        $userId = $request->session()->get('quickaccess.pending_user_id');

        if (!$userId) {
            return redirect()->route('member.quickaccess.start')
                ->withErrors(['member_code' => 'Please enter your member number to continue.']);
        }

        $user = User::find($userId);

        $request->session()->forget('quickaccess.pending_user_id');

        if (!$user) {
            return redirect()->route('member.quickaccess.start')
                ->withErrors(['member_code' => 'Member number not found! please contact admin']);
        }

        $status = strtolower(trim((string) $user->status));

        if (!in_array($status, ['active', 'inactive'], true)) {
            return redirect()->route('member.quickaccess.start')
                ->with('quickaccess_blocked', [
                    'message' => 'YOUR MEMBERSHIP NO. ' . $user->user_code . ' IS ' . strtoupper($status ?: 'NOT ACTIVE') . '.',
                ]);
        }

        Auth::guard('members')->login($user);
        $request->session()->put('LoggedMember', ['id' => $user->id, 'name' => $user->name]);

        return redirect()->route('member.quickaccess.pay');
    }

    public function pay(ClubmanMemberLookup $clubmanMemberLookup)
    {
        $user = Auth::guard('members')->user();

        abort_unless($user instanceof User, 401);

        $memberFinancials = null;

        try {
            $memberFinancials = $clubmanMemberLookup->lookup($user);
        } catch (\Throwable $exception) {
            // Fall through with a null summary; the view shows a "loading/unavailable" state.
        }

        return view('member.quick_access.pay', [
            'userData' => $user,
            'memberFinancials' => $memberFinancials,
        ]);
    }
}
