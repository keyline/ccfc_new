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
                    'token_url' => 'https://clubman.test/token',
                    'monthly_balance_url' => 'https://clubman.test/api/MemberMonthlyBalance/',
                    'monthly_balance_from_date' => '01-apr-2020',
                    'username' => 'CCFC',
                    'password' => null,
                    'token' => 'current-settings-token',
                    'verify_ssl' => false,
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

    public function test_it_accepts_case_variations_and_a_single_transaction_object(): void
    {
        $this->http->responsePayload = [
            'result' => 'SUCCESS',
            'Data' => [
                'Month' => 'Jul 2026',
                'LastBalance' => '100',
                'paidamount' => '25',
                'debitamount' => '10',
                'Balance' => '85',
            ],
        ];
        $user = new User();
        $user->user_code = 'B47CEO';

        $transactions = (new ClubmanInvoiceLookup())->lookup($user);

        $this->assertCount(1, $transactions);
        $this->assertSame('Jul 2026', $transactions[0]['Month']);
    }

    public function test_it_generates_a_fresh_access_token_when_credentials_are_configured(): void
    {
        $this->app['config']->set('services.clubman.password', 'clubman-password');
        $user = new User();
        $user->user_code = 'B47CEO';

        (new ClubmanInvoiceLookup())->lookup($user);

        $this->assertSame(1, $this->http->tokenRequestCount);
        $this->assertSame('fresh-access-token', $this->http->token);
    }
}

class ClubmanInvoiceHttpClient
{
    public $statusCode = 200;
    public $responsePayload;
    public $token;
    public $tokenRequestCount = 0;
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

    public function asForm()
    {
        return $this;
    }

    public function post($url, array $data = [])
    {
        if (strpos($url, '/token') !== false) {
            $this->tokenRequestCount++;

            return new ClubmanInvoiceHttpResponse(200, [
                'access_token' => 'fresh-access-token',
                'expires_in' => 3600,
            ]);
        }

        $this->url = $url;

        return new ClubmanInvoiceHttpResponse($this->statusCode, $this->responsePayload);
    }
}

class ClubmanInvoiceHttpResponse
{
    private $statusCode;
    private $payload;

    public function __construct(int $statusCode, array $payload = null)
    {
        $this->statusCode = $statusCode;
        $this->payload = $payload;
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
        return $this->payload ?: [
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
