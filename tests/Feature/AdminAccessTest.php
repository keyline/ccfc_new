<?php

namespace Tests\Feature;

use App\Http\Middleware\IsAdminMiddleware;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    public function test_guest_is_sent_to_the_login_page(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_session_is_logged_out_and_sent_to_the_login_page(): void
    {
        Auth::shouldReceive('logout')->once();

        $request = Request::create('/admin');
        $request->setUserResolver(function () {
            return (object) ['is_admin' => false];
        });
        $request->setLaravelSession($this->arraySession());

        $response = (new IsAdminMiddleware())->handle($request, function () {
            return response('admin dashboard');
        });

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('login'), $response->getTargetUrl());
        $this->assertSame(
            'Please log in with an administrator account.',
            $request->session()->get('error')
        );
    }

    public function test_admin_session_can_continue(): void
    {
        $request = Request::create('/admin');
        $request->setUserResolver(function () {
            return (object) ['is_admin' => true];
        });

        $response = (new IsAdminMiddleware())->handle($request, function () {
            return response('admin dashboard');
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('admin dashboard', $response->getContent());
    }

    private function arraySession(): Store
    {
        $session = new Store('testing', new ArraySessionHandler(120));
        $session->start();

        return $session;
    }
}
