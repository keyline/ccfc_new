<?php

namespace Tests\Unit;

use App\Http\Controllers\Member\PaymentController;
use App\Models\User;
use App\Services\ClubmanMemberLookup;
use Illuminate\Http\Request;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class PaymentMinimumValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Request::macro('validate', function (array $rules, array $messages = []) {
            $factory = new Factory(new Translator(new ArrayLoader(), 'en'));
            $validator = $factory->make($this->all(), $rules, $messages);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            return $validator->validated();
        });
    }

    protected function tearDown(): void
    {
        Request::flushMacros();

        parent::tearDown();
    }

    public function test_regular_gateway_rejects_an_amount_below_clubman_minimum(): void
    {
        $this->expectException(ValidationException::class);
        $this->validateAmount(249.99, false);
    }

    public function test_regular_and_paise_gateway_accept_the_clubman_minimum(): void
    {
        $this->assertSame(250.0, $this->validateAmount(250, false));
        $this->assertSame(25000.0, $this->validateAmount(25000, true));
    }

    public function test_paise_gateway_rejects_an_amount_below_clubman_minimum(): void
    {
        $this->expectException(ValidationException::class);
        $this->validateAmount(24999, true);
    }

    private function validateAmount($amount, bool $amountIsInPaise): float
    {
        $request = Request::create('/payment', 'POST', [
            'amount' => $amount,
            'paymentGatewayOptions' => '/payment-gateway',
        ]);
        $user = new User();
        $user->id = 42;
        $user->user_code = 'M42';
        $clubman = new class extends ClubmanMemberLookup {
            public function minimumPaymentAmount(User $user): float
            {
                return 250.0;
            }
        };
        $method = new ReflectionMethod(PaymentController::class, 'validatedPaymentAmount');
        $method->setAccessible(true);

        return $method->invoke(
            new PaymentController(),
            $request,
            $user,
            $clubman,
            $amountIsInPaise,
            true
        );
    }
}
