<?php

namespace App\Services;

use App\Models\Member;
use App\Models\PaymentToken;
use Illuminate\Support\Str;

class MagicLinkTokenService
{
    public function generate(Member $member)
    {
        return PaymentToken::create([
            'member_id' => $member->id,
            'token' => Str::random(60),
            'expires_at' => now()->addDay(),
        ]);
    }
}
