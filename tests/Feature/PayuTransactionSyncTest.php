<?php

namespace Tests\Feature;

use App\Services\MobilePayuClubmanPosting;
use App\Services\PayuTransactionSync;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tzsk\Payu\Checksum;

class PayuTransactionSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://localhost', 'database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        config(['payu_mobile' => ['key' => 'test-mobile', 'salt' => 'test-salt', 'mode' => 'test']]);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_code');
            $table->string('name')->default('Test Member');
            $table->softDeletes();
        });
        Schema::create('payment_details', function (Blueprint $table) {
            $table->id();
            $table->string('payu_txnid');
            $table->string('pg_name');
            $table->string('membership_no');
            $table->decimal('amount', 10, 2);
        });
        require_once database_path('migrations/2022_03_21_092518_create_payu_transactions_table.php');
        (new \CreatePayuTransactionsTable())->up();
        Schema::table('payu_transactions', function (Blueprint $table) {
            $table->string('payment_source')->nullable();
            $table->string('clubman_status')->nullable();
            $table->timestamp('clubman_checked_at')->nullable();
        });
        DB::table('users')->insert(['id' => 1, 'user_code' => 'M1']);
        $this->mock(MobilePayuClubmanPosting::class)->shouldReceive('post')->with('M1', 'order_1', 123.45)->andReturnNull();
    }

    private function response(string $status = 'success'): array
    {
        $data = ['txnid' => 'order_1', 'key' => config('payu_mobile.key'),
            'amount' => '123.45', 'status' => $status, 'udf1' => 'M1', 'mihpayid' => 'payu_1'];
        $data['hash'] = Checksum::with(config('payu_mobile.salt'))->generate($data, true);
        return $data;
    }

    public function test_duplicate_responses_update_one_row_and_keep_success(): void
    {
        $this->mock(MobilePayuClubmanPosting::class)->shouldReceive('post')->once()->with('M1', 'order_1', 123.45)->andReturnNull();
        $sync = app(PayuTransactionSync::class);
        $sync->pending('order_1', 1, '123.45');
        $id = DB::table('payu_transactions')->value('id');
        $createdAt = DB::table('payu_transactions')->value('created_at');
        $sync->receive($this->response('failure'), 1);
        $sync->receive($this->response(), 1);
        $sync->receive($this->response(), 1);
        $sync->receive($this->response('failure'), 1);
        $row = DB::table('payu_transactions')->sole();
        $this->assertSame($id, $row->id);
        $this->assertSame($createdAt, $row->created_at);
        $this->assertSame('successful', $row->status);
        $this->assertSame('payu_1', json_decode($row->response, true)['mihpayid']);
        $this->assertSame('success', $row->clubman_status);
    }

    public function test_webhook_recovers_mobile_payment_without_app_response(): void
    {
        DB::table('payment_details')->insert(['payu_txnid' => 'order_1', 'pg_name' => 'payu', 'membership_no' => 'M1', 'amount' => 123.45]);
        $this->postJson('http://localhost/api/payu/webhook', $this->response())->assertOk();
        $this->assertSame('successful', DB::table('payu_transactions')->sole()->status);
    }

    public function test_invalid_hash_does_not_save_a_payment(): void
    {
        $data = $this->response();
        $data['amount'] = '999';
        $this->postJson('http://localhost/api/payu/webhook', $data)->assertForbidden();
        $this->assertSame(0, DB::table('payu_transactions')->count());
    }

    public function test_another_member_cannot_overwrite_a_transaction(): void
    {
        app(PayuTransactionSync::class)->pending('order_1', 1, 123.45);
        try {
            app(PayuTransactionSync::class)->receive($this->response(), 2);
            $this->fail('Expected an ownership rejection.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertSame('pending', DB::table('payu_transactions')->sole()->status);
    }

    public function test_reconciliation_imports_old_mobile_attempt_and_saves_verified_details(): void
    {
        DB::table('payment_details')->insert(['payu_txnid' => 'order_1', 'pg_name' => 'payu', 'membership_no' => 'M1', 'amount' => 123.45]);
        Http::fake(['*.payu.in/*' => Http::response(['status' => 1, 'transaction_details' => [
            'order_1' => ['status' => 'success', 'amt' => '123.45', 'mihpayid' => 'payu_1', 'bank_ref_num' => 'bank_1'],
        ]])]);
        $this->artisan('payu:sync order_1')->assertExitCode(0);
        $this->assertSame('bank_1', json_decode(DB::table('payu_transactions')->sole()->response, true)['bank_ref_num']);
        Http::assertSent(fn ($request) => $request['command'] === 'verify_payment' && $request['var1'] === 'order_1');
    }

    public function test_network_failure_leaves_attempt_pending_for_retry(): void
    {
        app(PayuTransactionSync::class)->pending('order_1', 1, 123.45);
        Http::fake(['*' => Http::response([], 503)]);
        $this->artisan('payu:sync order_1')->assertExitCode(1);
        $this->assertSame('pending', DB::table('payu_transactions')->sole()->status);
    }

    public function test_command_imports_payu_only_transaction_and_updates_same_row_on_repeat(): void
    {
        $this->mock(MobilePayuClubmanPosting::class)->shouldReceive('post')->once()->with('M1', 'order_1', 123.45)->andReturnNull();
        Http::fake(['*' => Http::response(['status' => 1, 'transaction_details' => [
            'order_1' => ['status' => 'success', 'amt' => '123.45', 'mihpayid' => 'payu_1', 'udf1' => 'M1', 'productinfo' => 'M1'],
        ]])]);
        $this->artisan('payu:sync order_1')->expectsOutput('PayU transaction verified and imported. Check Admin > Payments.')->assertExitCode(0);
        $row = DB::table('payu_transactions')->sole();
        $this->assertSame(1, $row->paid_for_id);
        $this->assertSame('successful', $row->status);
        $this->artisan('payu:sync order_1')->assertExitCode(0);
        $this->assertSame($row->id, DB::table('payu_transactions')->sole()->id);
    }

    /** @dataProvider unmappablePayments */
    public function test_unregistered_transaction_is_not_saved_without_one_verified_member(array $details): void
    {
        DB::table('users')->insert(['id' => 2, 'user_code' => 'M2']);
        Http::fake(['*' => Http::response(['status' => 1, 'transaction_details' => [
            'order_1' => array_merge(['status' => 'success', 'amt' => '123.45'], $details),
        ]])]);
        $this->artisan('payu:sync order_1')->assertExitCode(1);
        $this->assertSame(0, DB::table('payu_transactions')->count());
    }

    public function unmappablePayments(): array
    {
        return [
            'missing member' => [[]],
            'unknown member' => [['udf1' => 'UNKNOWN']],
            'conflicting members' => [['udf1' => 'M1', 'productinfo' => 'M2']],
            'wrong transaction' => [['udf1' => 'M1', 'txnid' => 'other_order']],
            'invalid amount' => [['udf1' => 'M1', 'amt' => '0']],
        ];
    }

    public function test_payu_not_found_does_not_create_a_local_transaction(): void
    {
        Http::fake(['*' => Http::response(['status' => 0, 'transaction_details' => [
            'order_1' => ['status' => 'Not Found', 'mihpayid' => 'Not Found'],
        ]])]);
        $this->artisan('payu:sync order_1')->assertExitCode(1);
        $this->assertSame(0, DB::table('payu_transactions')->count());
    }

    public function test_missing_mobile_configuration_reports_a_clear_error(): void
    {
        config()->offsetUnset('payu_mobile');
        Http::fake();
        $this->artisan('payu:sync order_1')
            ->expectsOutput('PayU merchant key and salt are not configured.')
            ->assertExitCode(1);
        Http::assertNothingSent();
        $this->assertSame(0, DB::table('payu_transactions')->count());
    }

    public function test_amount_mismatch_does_not_overwrite_the_attempt(): void
    {
        app(PayuTransactionSync::class)->pending('order_1', 1, 200);
        $this->postJson('http://localhost/api/payu/webhook', $this->response())->assertStatus(422);
        $this->assertSame('pending', DB::table('payu_transactions')->sole()->status);
        $this->assertSame('200', json_decode(DB::table('payu_transactions')->sole()->response, true)['amount']);
    }

    public function test_failed_clubman_posting_keeps_payment_details_saved(): void
    {
        $this->mock(MobilePayuClubmanPosting::class)->shouldReceive('post')->once()->andThrow(new \RuntimeException('Clubman unavailable'));
        app(PayuTransactionSync::class)->pending('order_1', 1, 123.45);
        $this->postJson('http://localhost/api/payu/webhook', $this->response())->assertOk();
        $row = DB::table('payu_transactions')->sole();
        $this->assertSame('successful', $row->status);
        $this->assertSame('failure', $row->clubman_status);
    }

    public function test_saved_attempt_is_available_in_admin_payments(): void
    {
        app(PayuTransactionSync::class)->pending('order_1', 1, 123.45);
        \Illuminate\Support\Facades\Gate::shouldReceive('denies')->with('payment_access')->once()->andReturnFalse();
        $payments = (new \App\Http\Controllers\Admin\PaymentsController())->index()->getData()['payments'];
        $this->assertSame('order_1', $payments->sole()->transaction_id);
        $this->assertSame('pending', $payments->sole()->status);
    }

    public function test_website_reconciliation_updates_the_existing_row(): void
    {
        DB::table('payu_transactions')->insert([
            'transaction_id' => 'website_1', 'paid_for_id' => 1, 'paid_for_type' => \App\Models\User::class,
            'gateway' => serialize(new \Tzsk\Payu\Gateway\PayuBiz(config('payu_mobile'))),
            'body' => '', 'hash' => '', 'destination' => 'https://example.test/member/payment/status', 'status' => 'pending',
        ]);
        $this->mock(\App\Services\ClubmanPaymentPosting::class)->shouldReceive('postAndRecord')->once()->andReturn(['Status' => 'success']);
        Http::fake(['*' => Http::response(['status' => 1, 'transaction_details' => [
            'website_1' => ['status' => 'success', 'amt' => '123.45', 'mihpayid' => 'payu_2'],
        ]])]);
        app(PayuTransactionSync::class)->reconcile('website_1');
        $this->assertSame('successful', DB::table('payu_transactions')->sole()->status);
    }

    public function test_date_import_fetches_all_pages_and_updates_without_duplicates(): void
    {
        app(PayuTransactionSync::class)->pending('order_1', 1, 123.45);
        $originalId = DB::table('payu_transactions')->value('id');
        $this->mock(MobilePayuClubmanPosting::class)->shouldReceive('post')->once()->with('M1', 'order_1', 123.45)->andReturnNull();
        Http::fake(function ($request) {
            if ($request['command'] === 'get_Transaction_Details') {
                $rows = [1 => [['txnid' => 'order_1']], 2 => [['txnid' => 'order_2']]];
                return Http::response(['status' => 1, 'Transaction_details' => $rows[$request['var3']] ?? []]);
            }
            $id = $request['var1'];
            return Http::response(['status' => 1, 'transaction_details' => [$id => [
                'txnid' => $id, 'status' => $id === 'order_1' ? 'success' : 'failure',
                'amt' => '123.45', 'udf1' => 'M1', 'mihpayid' => 'payu_'.$id,
            ]]]);
        });
        $this->artisan('payu:sync --date=2026-11-26 --gateway=mobile')->assertExitCode(0);
        $this->artisan('payu:sync --date=2026-11-26 --gateway=mobile')->assertExitCode(0);
        $this->assertSame(2, DB::table('payu_transactions')->count());
        $this->assertSame($originalId, DB::table('payu_transactions')->where('transaction_id', 'order_1')->value('id'));
        $this->assertSame('failed', DB::table('payu_transactions')->where('transaction_id', 'order_2')->value('status'));
        Http::assertSent(fn ($request) => $request['command'] === 'get_Transaction_Details'
            && $request['var1'] === '2026-11-26' && $request['var2'] === '2026-11-26' && $request['var3'] === 2
            && $request['hash'] === hash('sha512', 'test-mobile|get_Transaction_Details|2026-11-26|test-salt'));
    }

    public function test_yesterday_uses_india_date_and_website_credentials_by_default(): void
    {
        config(['payu.default' => 'money', 'payu.gateways.money' => new \Tzsk\Payu\Gateway\PayuBiz([
            'key' => 'website-key', 'salt' => 'website-salt', 'mode' => 'test',
        ])]);
        $this->mock(\App\Services\ClubmanPaymentPosting::class)->shouldReceive('postAndRecord')->once()->andReturn(['Status' => 'success']);
        Http::fake(function ($request) {
            if ($request['command'] === 'get_Transaction_Details') {
                return Http::response(['status' => 1, 'Transaction_details' => $request['var3'] === 1 ? [['txnid' => 'website_1']] : []]);
            }
            return Http::response(['status' => 1, 'transaction_details' => [
                'website_1' => ['status' => 'success', 'amt' => '123.45', 'productinfo' => 'M1', 'mihpayid' => 'payu_1'],
            ]]);
        });
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-11-27 00:30:00', 'Asia/Kolkata'));
        try {
            $this->artisan('payu:sync --date=yesterday')->assertExitCode(0);
            Http::assertSent(fn ($request) => $request['command'] === 'get_Transaction_Details'
                && $request['key'] === 'website-key' && $request['var1'] === '2026-11-26');
            $this->assertSame('member_login', DB::table('payu_transactions')->sole()->payment_source);
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_date_import_reports_clubman_failure_after_saving_payment(): void
    {
        $this->mock(MobilePayuClubmanPosting::class)->shouldReceive('post')->once()->andThrow(new \RuntimeException('Clubman unavailable'));
        Http::fake(function ($request) {
            if ($request['command'] === 'get_Transaction_Details') {
                return Http::response(['status' => 1, 'Transaction_details' => $request['var3'] === 1 ? [['txnid' => 'order_1']] : []]);
            }
            return Http::response(['status' => 1, 'transaction_details' => ['order_1' => [
                'status' => 'success', 'amt' => '123.45', 'udf1' => 'M1', 'mihpayid' => 'payu_1',
            ]]]);
        });
        $this->artisan('payu:sync --date=2026-11-26 --gateway=mobile')
            ->expectsOutput('order_1: payment saved; Clubman posting needs retry.')->assertExitCode(1);
        $this->assertSame('successful', DB::table('payu_transactions')->sole()->status);
    }

    public function test_invalid_date_does_not_contact_payu(): void
    {
        Http::fake();
        $this->artisan('payu:sync --date=2026-02-30 --gateway=mobile')->assertExitCode(1);
        Http::assertNothingSent();
    }

    public function test_repeated_report_page_is_reported_instead_of_looping(): void
    {
        Http::fake(['*' => Http::response(['status' => 1, 'Transaction_details' => [['txnid' => 'order_1']]])]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PayU repeated a report page.');
        iterator_to_array(app(PayuTransactionSync::class)->transactionsForDate('2026-11-26', 'mobile'));
    }
}
