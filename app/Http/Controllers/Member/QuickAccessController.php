<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ClubmanMemberLookup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class QuickAccessController extends Controller
{
    private const OTP_TTL_MINUTES = 5;
    private const MAX_VERIFY_ATTEMPTS = 5;
    private const MAX_OTP_REQUESTS = 3;
    private const OTP_REQUEST_DECAY_SECONDS = 600;

    public function showMemberNumberForm()
    {
        return view('member.quick_access.member_number');
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'member_code' => 'required|string',
        ]);

        $user = User::where('user_code', $request->member_code)->first();

        if (!$user) {
            return back()->withErrors(['member_code' => 'Member number not found! please contact admin']);
        }

        $rateLimitKey = 'quickaccess-otp:' . $user->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_OTP_REQUESTS)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return back()->withErrors([
                'member_code' => "Too many OTP requests. Please try again in {$seconds} seconds.",
            ]);
        }

        $memberDetail = $user->userCodeUserDetails()->first();
        $mobile = trim((string) $user->phone_number_1) ?: trim((string) optional($memberDetail)->mobile_no);
        $email = trim((string) $user->email) ?: trim((string) optional($memberDetail)->email);

        if ($mobile === '' && $email === '') {
            return back()->withErrors([
                'member_code' => 'No contact details on file for this member. Please contact the club office.',
            ]);
        }

        RateLimiter::hit($rateLimitKey, self::OTP_REQUEST_DECAY_SECONDS);

        $otp = random_int(100000, 999999);

        Cache::put(
            'quickaccess:otp:' . $user->id,
            [
                'code_hash' => hash('sha256', (string) $otp),
                'attempts' => 0,
            ],
            now()->addMinutes(self::OTP_TTL_MINUTES)
        );

        if ($mobile !== '') {
            $message = "Dear%20User%2C%0AOTP%20for%20logging%20in%20to%20the%20CC%26FC%20app%20is%20" . $otp . ".%20Valid%20for%20" . self::OTP_TTL_MINUTES . "%20minutes.";
            $this->sendSMS($mobile, $message);
        }

        if ($email !== '') {
            $mailData = ['otp' => $otp];
            $subject = 'CCFC :: OTP For Quick Access Login';
            $body = view('email-templates.otp', $mailData)->render();
            $this->sendMail($email, $subject, $body);
        }

        $request->session()->put('quickaccess.pending_user_id', $user->id);
        $request->session()->put('quickaccess.member_code', $user->user_code);
        $request->session()->put('quickaccess.contact_hint', $this->maskContact($mobile, $email));

        return redirect()->route('member.quickaccess.verify.show');
    }

    public function showOtpForm(Request $request)
    {
        if (!$request->session()->has('quickaccess.pending_user_id')) {
            return redirect()->route('member.quickaccess.start')
                ->withErrors(['member_code' => 'Please enter your member number to continue.']);
        }

        return view('member.quick_access.verify_otp', [
            'contactHint' => $request->session()->get('quickaccess.contact_hint'),
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $userId = $request->session()->get('quickaccess.pending_user_id');

        if (!$userId) {
            return redirect()->route('member.quickaccess.start')
                ->withErrors(['member_code' => 'Please enter your member number to continue.']);
        }

        $user = User::find($userId);

        if (!$user) {
            $request->session()->forget(['quickaccess.pending_user_id', 'quickaccess.member_code', 'quickaccess.contact_hint']);

            return redirect()->route('member.quickaccess.start')
                ->withErrors(['member_code' => 'Member number not found! please contact admin']);
        }

        $cacheKey = 'quickaccess:otp:' . $user->id;
        $otpData = Cache::get($cacheKey);

        if (!$otpData) {
            return back()->withErrors(['otp' => 'This OTP has expired. Please request a new one.']);
        }

        if (hash('sha256', (string) $request->otp) !== $otpData['code_hash']) {
            $otpData['attempts']++;

            if ($otpData['attempts'] >= self::MAX_VERIFY_ATTEMPTS) {
                Cache::forget($cacheKey);
                $request->session()->forget(['quickaccess.pending_user_id', 'quickaccess.member_code', 'quickaccess.contact_hint']);

                return redirect()->route('member.quickaccess.start')
                    ->withErrors(['member_code' => 'Too many incorrect attempts. Please request a new OTP.']);
            }

            Cache::put($cacheKey, $otpData, now()->addMinutes(self::OTP_TTL_MINUTES));

            return back()->withErrors(['otp' => 'Incorrect OTP. Please try again.']);
        }

        Cache::forget($cacheKey);
        $request->session()->forget(['quickaccess.pending_user_id', 'quickaccess.member_code', 'quickaccess.contact_hint']);

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

    private function maskContact(string $mobile, string $email): string
    {
        if ($mobile !== '') {
            return 'mobile number ending in ' . substr($mobile, -4);
        }

        if ($email !== '') {
            $parts = explode('@', $email);
            $name = $parts[0] ?? '';
            $domain = $parts[1] ?? '';
            $maskedName = strlen($name) > 2 ? substr($name, 0, 2) . str_repeat('*', max(1, strlen($name) - 2)) : $name;

            return "email {$maskedName}@{$domain}";
        }

        return 'your registered contact details';
    }
}
