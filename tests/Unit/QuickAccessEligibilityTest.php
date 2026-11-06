<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\QuickAccessEligibility;
use PHPUnit\Framework\TestCase;

class QuickAccessEligibilityTest extends TestCase
{
    public function test_all_active_and_inactive_members_are_allowed(): void
    {
        foreach (['B47CEO', 'B48CEO'] as $code) {
            foreach (['ACTIVE', 'INACTIVE', 'active', 'inactive', ' Active ', ' Inactive '] as $status) {
                $this->assertTrue($this->allows($code, $status));
            }
        }
    }

    public function test_other_member_statuses_are_blocked(): void
    {
        foreach (['SUSPENDED', 'DELETED', '', 'pending'] as $status) {
            $this->assertFalse($this->allows('B47CEO', $status));
        }
    }

    private function allows(string $code, string $status): bool
    {
        $user = new User();
        $user->forceFill(['user_code' => $code, 'status' => $status]);

        return (new QuickAccessEligibility())->allows($user);
    }
}
