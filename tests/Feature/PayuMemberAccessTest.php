<?php

namespace Tests\Feature;

use App\Http\Controllers\Member\PaymentController;
use App\Models\User;
use App\Services\ClubmanMemberLookup;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PayuMemberAccessTest extends TestCase
{
    /** @dataProvider members */
    public function test_only_allowed_member_reaches_payment_validation(string $code, bool $allowed): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_code');
            $table->softDeletes();
        });
        DB::table('users')->insert(['id' => 1, 'user_code' => $code]);
        session(['LoggedMember' => 1]);
        $lookup = \Mockery::mock(ClubmanMemberLookup::class);
        $lookup->shouldReceive('minimumPaymentAmount')->times($allowed ? 1 : 0)->andReturn(1.0);

        try {
            (new PaymentController())->payment(Request::create('/member/payment', 'POST'), $lookup);
            $this->fail('Payment should not start without valid input.');
        } catch (ValidationException $exception) {
            $this->assertTrue($allowed);
        } catch (HttpException $exception) {
            $this->assertFalse($allowed);
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public static function members(): array
    {
        return [['B47CEO', true], ['M1', false], ['b47ceo', false]];
    }
}
