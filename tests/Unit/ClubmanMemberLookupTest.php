<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\ClubmanMemberLookup;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

class ClubmanMemberLookupTest extends TestCase
{
    private $app;
    private $cache;
    private $http;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Container();
        Container::setInstance($this->app);
        Facade::setFacadeApplication($this->app);

        $this->cache = new ClubmanLookupArrayCache();
        $this->http = new ClubmanLookupHttpClient();

        $this->app->instance('cache', $this->cache);
        $this->app->instance(Factory::class, $this->http);
        $this->app->instance('config', new Repository([
            'services' => [
                'clubman' => [
                    'token_url' => 'https://clubman.test/token',
                    'member_lookup_url' => 'https://clubman.test/api/MemberLookup',
                    'username' => 'api-user',
                    'password' => 'api-password',
                    'token' => null,
                    'verify_ssl' => false,
                    'timeout' => 8,
                    'connect_timeout' => 3,
                ],
            ],
        ]));
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_it_generates_a_token_and_caches_normalized_member_balances(): void
    {
        $user = new User();
        $user->id = 42;
        $user->user_code = 'B47CEO';
        $service = new ClubmanMemberLookup();

        $first = $service->lookup($user);
        $second = $service->lookup($user);

        $this->assertSame($first, $second);
        $this->assertCount(2, $this->http->requests);
        $this->assertSame('password', $this->http->requests[0]['data']['grant_type']);
        $this->assertSame('api-user', $this->http->requests[0]['data']['UserName']);
        $this->assertStringContainsString('MCODE=B47CEO', $this->http->requests[1]['url']);
        $this->assertSame('B47CEO', $this->http->requests[1]['data']['MCODE']);
        $this->assertSame('generated-token', $this->http->requests[1]['token']);
        $this->assertGreaterThan(0, $this->http->withoutVerificationCalls);
        $this->assertSame(-12698.25, $first['outstanding']);
        $this->assertSame(0.0, $first['minimum_due_amount']);
        $this->assertSame(1.0, $service->minimumPaymentAmount($user));
    }

    public function test_it_uses_the_clubman_minimum_due_as_the_payment_floor(): void
    {
        $this->http->minimumDue = 250.75;
        $user = new User();
        $user->id = 43;
        $user->user_code = 'M43';

        $this->assertSame(250.75, (new ClubmanMemberLookup())->minimumPaymentAmount($user));
    }
}

class ClubmanLookupArrayCache
{
    public $values = [];

    public function get($key, $default = null)
    {
        return $this->values[$key] ?? $default;
    }

    public function put($key, $value, $ttl = null)
    {
        $this->values[$key] = $value;

        return true;
    }

    public function forget($key)
    {
        unset($this->values[$key]);

        return true;
    }
}

class ClubmanLookupHttpClient
{
    public $requests = [];
    public $minimumDue = 0.0;
    public $withoutVerificationCalls = 0;
    private $headers = [];
    private $token;

    public function acceptJson()
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

    public function withoutVerifying()
    {
        $this->withoutVerificationCalls++;

        return $this;
    }

    public function withToken($token)
    {
        $this->token = $token;

        return $this;
    }

    public function withHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    public function asMultipart()
    {
        return $this;
    }

    public function asForm()
    {
        return $this;
    }

    public function post($url, array $data = [])
    {
        $this->requests[] = [
            'url' => $url,
            'data' => $data,
            'token' => $this->token,
            'headers' => $this->headers,
        ];

        return new ClubmanLookupHttpResponse(
            strpos($url, '/token') !== false,
            $this->minimumDue,
            (string) ($data['MCODE'] ?? '')
        );
    }
}

class ClubmanLookupHttpResponse
{
    private $tokenResponse;
    private $minimumDue;
    private $memberCode;

    public function __construct(bool $tokenResponse, float $minimumDue, string $memberCode)
    {
        $this->tokenResponse = $tokenResponse;
        $this->minimumDue = $minimumDue;
        $this->memberCode = $memberCode;
    }

    public function successful()
    {
        return true;
    }

    public function status()
    {
        return 200;
    }

    public function json()
    {
        if ($this->tokenResponse) {
            return [
                'access_token' => 'generated-token',
                'expires_in' => 3600,
            ];
        }

        return [
            'Result' => 'success',
            'ErrorMsg' => '',
            'data' => [
                'MembershipID' => $this->memberCode,
                'MemberName' => 'SHIBASHIS BANERJEE',
                'Status' => 'ACTIVE',
                'MobileNo' => '9874472625',
                'Outstanding' => -12698.25,
                'MinimumDueAmount' => $this->minimumDue,
            ],
        ];
    }
}
