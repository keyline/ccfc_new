<?php

namespace Tests\Unit;

use App\Services\ClubmanPaymentPosting;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ClubmanPaymentStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $database = new Manager();
        $database->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        DB::swap($database->getDatabaseManager());
        DB::statement('CREATE TABLE payu_transactions (transaction_id TEXT, status TEXT, clubman_status TEXT, clubman_checked_at TEXT)');
        foreach (['order_1', 'order_2'] as $order) {
            DB::table('payu_transactions')->insert(['transaction_id' => $order, 'status' => 'successful']);
        }
    }

    protected function tearDown(): void
    {
        DB::clearResolvedInstances();
        parent::tearDown();
    }

    public function test_success_is_saved_on_the_matching_order_only(): void
    {
        $service = $this->service(false);
        $service->postAndRecord('order_1', 'M1', 'payment_1', 10, 'payment_1');
        $row = DB::table('payu_transactions')->where('transaction_id', 'order_1')->first();
        $this->assertSame('success', $row->clubman_status);
        $this->assertNotNull($row->clubman_checked_at);
        $this->assertSame('successful', $row->status);
        $this->assertNull(DB::table('payu_transactions')->where('transaction_id', 'order_2')->value('clubman_status'));
    }

    public function test_failure_is_saved_without_changing_gateway_success_and_is_rethrown(): void
    {
        try {
            $this->service(true)->postAndRecord('order_1', 'M1', 'payment_1', 10, 'payment_1');
            $this->fail('Expected Clubman rejection.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Clubman rejected posting', $exception->getMessage());
        }
        $row = DB::table('payu_transactions')->where('transaction_id', 'order_1')->first();
        $this->assertSame('failure', $row->clubman_status);
        $this->assertNotNull($row->clubman_checked_at);
        $this->assertSame('successful', $row->status);
    }

    private function service(bool $fail): ClubmanPaymentPosting
    {
        return new class($fail) extends ClubmanPaymentPosting {
            private $fail;

            public function __construct(bool $fail) { $this->fail = $fail; }

            public function post(string $memberCode, string $voucherNo, float $amount, string $instrumentNo, string $description = 'Online payment against outstanding', string $paymentGateway = ''): array
            {
                if ($this->fail) {
                    throw new RuntimeException('Clubman rejected posting');
                }
                return ['Status' => 'success'];
            }
        };
    }
}
