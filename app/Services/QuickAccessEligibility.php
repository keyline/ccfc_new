<?php

namespace App\Services;

use App\Models\User;

class QuickAccessEligibility
{
    public function allows(User $user): bool
    {
        if (strtolower(trim((string) $user->status)) !== 'active') {
            return false;
        }

        return (bool) config('quick_access.allow_all_active_members', false)
            || in_array(strtoupper(trim((string) $user->user_code)), config('quick_access.allowed_member_codes', ['B47CEO']), true);
    }
}
