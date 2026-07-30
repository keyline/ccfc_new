<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    public function test_non_admin_credentials_are_rejected_without_a_route_error(): void
    {
        $guard = Mockery::mock();
        $guard->shouldReceive('logout')->once();
        Auth::shouldReceive('guard')->once()->andReturn($guard);

        $request = Request::create('/login', 'POST');
        $request->setLaravelSession($this->arraySession());

        $response = $this->controller()->handleAuthenticated(
            $request,
            (object) ['is_admin' => false]
        );

        $this->assertSame(route('login'), $response->getTargetUrl());
        $this->assertSame(
            'This account does not have administrator access.',
            $request->session()->get('errors')->first('email')
        );
    }

    public function test_admin_login_redirects_to_the_admin_dashboard(): void
    {
        $this->assertSame(
            route('admin.home'),
            $this->controller()->redirectPath()
        );
    }

    private function controller(): TestableAdminLoginController
    {
        return new TestableAdminLoginController();
    }

    private function arraySession(): Store
    {
        $session = new Store('testing', new ArraySessionHandler(120));
        $session->start();

        return $session;
    }
}

class TestableAdminLoginController extends LoginController
{
    public function handleAuthenticated(Request $request, $user)
    {
        return $this->authenticated($request, $user);
    }
}
