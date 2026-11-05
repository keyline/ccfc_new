<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\QuickAccessEligibility;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

class QuickAccessEligibilityTest extends TestCase
{
    public function test_pilot_allows_only_the_selected_active_member(): void
    {
        $this->withConfig(false, function () {
            $this->assertTrue($this->allows('B47CEO', 'ACTIVE'));
            $this->assertFalse($this->allows('B48CEO', 'ACTIVE'));
            $this->assertFalse($this->allows('B47CEO', 'INACTIVE'));
            $this->assertFalse($this->allows('B47CEO', 'SUSPENDED'));
        });
    }

    public function test_future_rollout_allows_all_active_members_only(): void
    {
        $this->withConfig(true, function () {
            $this->assertTrue($this->allows('B48CEO', 'ACTIVE'));
            $this->assertFalse($this->allows('B48CEO', 'INACTIVE'));
            $this->assertFalse($this->allows('B47CEO', 'SUSPENDED'));
        });
    }

    private function allows(string $code, string $status): bool
    {
        $user = new User();
        $user->forceFill(['user_code' => $code, 'status' => $status]);

        return (new QuickAccessEligibility())->allows($user);
    }

    private function withConfig(bool $allowAll, callable $assertions): void
    {
        $previous = Container::getInstance();
        $container = new Container();
        $container->instance('config', new Repository([
            'quick_access' => [
                'allow_all_active_members' => $allowAll,
                'allowed_member_codes' => ['B47CEO'],
            ],
        ]));
        Container::setInstance($container);

        try {
            $assertions();
        } finally {
            Container::setInstance($previous);
        }
    }
}
