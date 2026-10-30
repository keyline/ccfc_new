<?php

namespace Tests\Unit;

use App\Services\PaymentSource;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use PHPUnit\Framework\TestCase;

class PaymentSourceTest extends TestCase
{
    public function test_quick_access_requires_both_form_marker_and_session(): void
    {
        $request = $this->request(['payment_source' => 'quick_access']);
        $this->assertSame('member_login', PaymentSource::fromRequest($request));

        $request->session()->put('quickaccess.payment_in_progress', true);
        $this->assertSame('quick_access', PaymentSource::fromRequest($request));
    }

    public function test_regular_payment_is_not_classified_by_a_stale_quick_access_session(): void
    {
        $request = $this->request();
        $request->session()->put('quickaccess.payment_in_progress', true);
        $this->assertSame('member_login', PaymentSource::fromRequest($request));
    }

    public function test_payment_link_is_identified_separately(): void
    {
        $request = $this->request();
        $request->session()->put('tokenPayment.active_id', 42);
        $this->assertSame('payment_link', PaymentSource::fromRequest($request));
    }

    public function test_historical_or_unrecognized_sources_are_unknown(): void
    {
        $this->assertSame('-', PaymentSource::label(null));
        $this->assertSame('-', PaymentSource::label('legacy'));
        $this->assertSame('Quick Access', PaymentSource::label('quick_access'));
    }

    private function request(array $data = []): Request
    {
        $request = Request::create('/member/payment', 'POST', $data);
        $request->setLaravelSession(new Store('test', new ArraySessionHandler(120)));

        return $request;
    }
}
