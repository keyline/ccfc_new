<?php

namespace Tests\Feature;

use App\Http\Controllers\Member\PaymentController;
use App\Services\ClubmanPaymentPosting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tzsk\Payu\Facades\Payu;
use Tzsk\Payu\Models\PayuTransaction;

class PayuClubmanPostingTest extends TestCase
{
    public function test_hdfc_vas_posts_for_a_regular_member(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'auth.logout_after_payment' => false]);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('user_code');
            $table->softDeletes();
        });
        Schema::create('member_dues', function (Blueprint $table) {
            $table->string('member_code');
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'Test Member', 'user_code' => 'M1']);
        Notification::fake();
        $gateway = \Mockery::mock(\App\PaymentGateways\PaymentGatewayInterface::class);
        $gateway->shouldReceive('verifyPayment')->once()->with('encrypted')->andReturn([
            'status' => 'success', 'user' => 1, 'transactionid' => 'hdfc_1', 'amount' => '123.45',
        ]);
        $this->mock(ClubmanPaymentPosting::class)->shouldReceive('postAndRecord')->once()->with(
            'hdfc_1', 'M1', 'hdfc_1', 123.45, 'hdfc_1', 'HDFC VAS payment against outstanding', 'HDFC VAS'
        )->andReturn(['Status' => 'success']);
        $request = Request::create('/member/payment/hdfcstatus', 'POST', ['respData' => 'encrypted']);
        $request->setLaravelSession(app('session.store'));

        $view = (new PaymentController())->statusForHdfc($gateway, $request);

        $this->assertFalse($view->getData()['status']['clubman_posting_failed']);
    }

    public function test_mobile_razorpay_posts_for_a_regular_member_and_records_result(): void
    {
        $user = new \App\Models\User();
        $user->user_code = 'M1';
        $this->mock(ClubmanPaymentPosting::class)->shouldReceive('postAndRecord')->once()->with(
            'pay_1', 'M1', 'pay_1', 123.45, 'pay_1', 'Razorpay payment against outstanding', 'Razorpay'
        )->andReturn(['Status' => 'success']);
        $method = new \ReflectionMethod(\App\Http\Controllers\Api\V2\Member\ApiController::class, 'postMobilePaymentToClubman');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke(new \App\Http\Controllers\Api\V2\Member\ApiController(), $user, 'pay_1', 123.45, 'Razorpay'));
    }

    /** @dataProvider callbackStatuses */
    public function test_callback_posts_only_successful_payments_not_already_posted($paymentStatus, $clubmanStatus, $postingFails, $shouldPost): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'auth.logout_after_payment' => false]);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('user_code');
            $table->softDeletes();
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'Test Member', 'user_code' => 'M1']);
        Notification::fake();

        $transaction = new PayuTransaction();
        $transaction->forceFill([
            'transaction_id' => 'order_1', 'status' => $paymentStatus,
            'clubman_status' => $clubmanStatus,
            'response' => ['udf1' => 1, 'mihpayid' => 'payu_1', 'amount' => '123.45'],
        ]);
        Payu::shouldReceive('capture')->once()->andReturn($transaction);
        $posting = $this->mock(ClubmanPaymentPosting::class);
        if ($shouldPost) {
            $expectation = $posting->shouldReceive('postAndRecord')->once()->with(
                'order_1', 'M1', 'payu_1', 123.45, 'payu_1', 'PayU payment against outstanding', 'PayU'
            );
            if ($postingFails) {
                $expectation->andThrow(new \RuntimeException('Clubman unavailable'));
            } else {
                $expectation->andReturn(['Status' => 'success']);
            }
        } else {
            $posting->shouldNotReceive('postAndRecord');
        }
        $request = Request::create('/member/payment/status');
        $request->setLaravelSession(app('session.store'));

        $view = (new PaymentController())->status($request);

        $this->assertSame('member.paymentstatus', $view->name());
        $this->assertSame($postingFails, $view->getData()['status']['clubman_posting_failed'] ?? false);
    }

    public function callbackStatuses(): array
    {
        return [
            'successful payment' => ['successful', null, false, true],
            'Clubman failure' => ['successful', null, true, true],
            'already posted' => ['successful', 'success', false, false],
            'failed payment' => ['failed', null, false, false],
        ];
    }
}
