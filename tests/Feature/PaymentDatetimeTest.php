<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;
use Tzsk\Payu\Models\PayuTransaction;

class PaymentDatetimeTest extends TestCase
{
    /** @dataProvider paymentDates */
    public function test_payment_date_uses_payu_time_with_a_safe_fallback($gateway, $addedon, $expectedDate, $expectedOrder): void
    {
        $payment = new PayuTransaction();
        $payment->forceFill(['updated_at' => '2026-11-27 17:36:25', 'response' => ['addedon' => $addedon]]);
        $view = file_get_contents(resource_path('views/admin/payments/index.blade.php'));
        preg_match('/@php\s+\$paymentDate =.*?<\/td>/s', $view, $fragment);
        $this->assertNotEmpty($fragment);
        $html = Blade::render($fragment[0], compact('payment', 'gateway'), true);
        $this->assertStringContainsString($expectedDate, $html);
        $this->assertStringContainsString('data-order="'.$expectedOrder.'"', $html);
    }

    public function paymentDates(): array
    {
        return [
            'original PayU time' => ['status', '2026-11-26 19:06:11', '2026-11-26 07:06:11 PM', '2026-11-26 19:06:11'],
            'missing PayU time' => ['status', null, '2026-11-27 05:36:25 PM', '2026-11-27 17:36:25'],
            'invalid PayU time' => ['status', 'invalid', '2026-11-27 05:36:25 PM', '2026-11-27 17:36:25'],
            'other gateway' => ['razorpaycallback', '2026-11-26 19:06:11', '2026-11-27 05:36:25 PM', '2026-11-27 17:36:25'],
        ];
    }
}
