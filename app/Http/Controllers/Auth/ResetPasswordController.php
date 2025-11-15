<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    // protected $redirectTo = RouteServiceProvider::HOME;
    protected $redirectTo = '/';

    //protected $redirectTo = RouteServiceProvider::HOME;

    protected function rules()
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:4',
        ];
    }

    public function showResetForm(Request $request, $token = null)
    {

        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email, 'user_code' => $request->usercode]
        );
    }

    public function reset(Request $request)
    {
        $request->validate([
              'email' => 'required|email|exists:users',
              'password' => 'required|string|min:4|confirmed',
              'password_confirmation' => 'required',
              'user_code'             => 'required',
              'token' => 'required|string', // Added token validation
          ]);
        // Find the password reset record
        $passwordReset = DB::table('password_resets')
                              ->where([
                                'email' => $request->email,
                                'user_code' => $request->user_code,
                              ])
                              ->first();

        if (!$passwordReset) {
            return redirect()->back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => 'Invalid reset request. Please try again.']);
        }

        // Check if token is expired (optional - add created_at timestamp check)
        $tokenAge = Carbon::parse($passwordReset->created_at);

        if ($tokenAge->diffInMinutes(now()) > config('auth.passwords.users.expire', 60)) {
            // Delete expired token
            DB::table('password_resets')
                ->where('email', $request->email)
                ->where('user_code', $request->user_code)
                ->delete();

            return redirect()->back()
                ->withInput($request->only('email'))
                ->withErrors(['token' => 'Reset token has expired. Please request a new one.']);
        }

        //Validating token
        /*if (Hash::check($request->token, $passwordReset->token)) {
            $user = tap(User::where('user_code', $request->user_code)->select('id', 'email_verified_at', 'email', 'user_code'))
                      ->update(
                          ['password' => Hash::make($request->password)]
                      )->first();
            //first time track through email_verified_at
            if (is_null($user->email_verified_at)) {
                //return back()->with('status', 'Your account is not activated. Please activate it first.');

                $user->email_verified_at = date('Y-m-d H:i:s');
                $user->save();
            }

            //login the user immediately they change password successfully
            //Auth::login($user);

            //Delete the token
            DB::table('password_resets')->where('email', $user->email)
                                    ->where('user_code', $user->user_code)
                                    ->delete();

            return redirect()->route('member.login')->with('message', 'Password updated successfully.');
        } else {
            return redirect()->back()->with('error', "Token mismatch!");
        }*/


        // Find and update the user
        $user = User::where('user_code', $request->user_code)
            ->where('email', $request->email)
            ->first();

        if (!$user) {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'User not found.']);
        }

        // Update the user's password
        $user->password = Hash::make($request->password);

        // Mark email as verified if not already verified (first time activation)
        if (is_null($user->email_verified_at)) {
            $user->email_verified_at = now();
        }

        // Save the user
        $user->save();

        // Delete the used password reset token
        DB::table('password_resets')
            ->where('email', $user->email)
            ->where('user_code', $user->user_code)
            ->delete();

        // Redirect with success message
        return redirect()->route('member.login')
            ->with('msg_passwd_reset', 'Password updated successfully.');


    }
}
