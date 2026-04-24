<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\MagicLinkTokenService;
use App\Mail\MagicLinkEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MemberDuesController extends Controller
{
    public function sendMagicLink(Request $request, MagicLinkTokenService $tokenService)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
        ]);

        $member = Member::find($request->member_id);

        $token = $tokenService->generate($member);

        Mail::to($member->select_member->email)->send(new MagicLinkEmail($token));

        return back()->with('success', 'Magic link sent successfully!');
    }
}
