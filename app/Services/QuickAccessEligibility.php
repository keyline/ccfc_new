<?php

namespace App\Services;

use App\Models\User;

class QuickAccessEligibility
{
    public function allows(User $user): bool
    {
        return in_array(strtolower(trim((string) $user->status)), ['active', 'inactive'], true);
    }
}
