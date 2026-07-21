<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\ClubmanInvoiceLookup;
use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ClubmanInvoiceLookupTest extends TestCase
{
    private $app;
    private $http;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Container();
        Container::setInstance($this->app);
        Facade::setFacadeApplication($this->app);

        $this->http = new ClubmanInvoiceHttpClient();
        $this->app->instance(Factory::class, $this->http);
        $this->app->instance('config', new Repository([
            'services' => [
                'clubman' => [
                    'monthly_balance_url' => 'https://clubman.test/api/MemberMonthlyBalance/',
                    'monthly_balance_from_date' => '01-apr-2020',
                    'token' => 'current-settings-token',
                    'timeout' => 15,
                    'connect_timeout' => 5,
                ],
            ],
        ]));

        Carbon::setTestNow(Carbon::parse('2026-07-21 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_it_uses_the_configured_token_and_current_date_for_invoices(): void
    {
        $user = new User();
        $user->user_code = 'B47CEO';

        $transactions = (new ClubmanInvoiceLookup())->lookup($user);

        $this->assertCount(1, $transactions);
        $this->assertSame('Jul 2026', $transactions[0]['Month']);
        $this->assertSame('current-settings-token', $this->http->token);
        $this->assertTrue($this->http->withoutVerification);
        $this->assertStringContainsString('MCODE=B47CEO', $this->http->url);
        $this->assertStringContainsString('FromDate=01-apr-2020', $this->http->url);
        $this->assertStringContainsString('ToDate=21-jul-2026', $this->http->url);
    }

    public function test_it_reports_an_expired_or_invalid_token(): void
    {
        $this->http->statusCode = 401;
        $user = new User();
        $user->user_code = 'B47CEO';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invoice API token');

        (new ClubmanInvoiceLookup())->lookup($user);
    }
}

class ClubmanInvoiceHttpClient
{
    public $statusCode = 200;
    public $token;
    public $url;
    public $withoutVerification = false;

    public function withoutVerifying()
    {
        $this->withoutVerification = true;

        return $this;
    }

    public function acceptJson()
    {
        return $this;
    }

    public function withToken($token)
    {
        $this->token = $token;

        return $this;
    }

    public function withHeaders(array $headers)
    {
        return $this;
    }

    public function timeout($seconds)
    {
        return $this;
    }

    public function withOptions(array $options)
    {
        return $this;
    }

    public function post($url)
    {
        $this->url = $url;

        return new ClubmanInvoiceHttpResponse($this->statusCode);
    }
}

class ClubmanInvoiceHttpResponse
{
    private $statusCode;

    public function __construct(int $statusCode)
    {
        $this->statusCode = $statusCode;
    }

    public function status()
    {
        return $this->statusCode;
    }

    public function successful()
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function json()
    {
        return [
            'Result' => 'success',
            'ErrorMsg' => '',
            'data' => [[
                'Month' => 'Jul 2026',
                'LastBalance' => '100',
                'paidamount' => '25',
                'debitamount' => '10',
                'Balance' => '85',
            ]],
        ];
    }
}
