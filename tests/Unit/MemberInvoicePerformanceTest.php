<?php

namespace Tests\Unit;

use App\Helpers\SearchInvoicePdf;
use App\Http\Controllers\Member\HomeController;
use App\Models\User;
use Illuminate\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class MemberInvoicePerformanceTest extends TestCase
{
    private $app;
    private $cache;
    private $filesystem;
    private $http;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Container();
        Container::setInstance($this->app);
        Facade::setFacadeApplication($this->app);

        $this->cache = new InvoiceArrayCache();
        $this->filesystem = new InvoiceFilesystem();
        $this->http = new InvoiceHttpClient();

        $this->app->instance('cache', $this->cache);
        $this->app->instance('filesystem', $this->filesystem);
        $this->app->instance(Factory::class, $this->http);
        $this->app->instance('url', new InvoiceUrlGenerator());
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_monthly_balances_are_reused_from_cache(): void
    {
        $user = new User();
        $user->id = 42;
        $user->user_code = 'M42';

        $method = new ReflectionMethod(HomeController::class, 'invoiceTransactions');
        $method->setAccessible(true);
        $controller = new HomeController();

        $first = $method->invoke($controller, $user);
        $second = $method->invoke($controller, $user);

        $this->assertSame(1, $this->http->postCount);
        $this->assertSame(8, $this->http->timeoutSeconds);
        $this->assertSame(3, $this->http->options['connect_timeout']);
        $this->assertSame($first, $second);
        $this->assertCount(2, $this->filesystem->checkedPaths);
        $this->assertArrayHasKey('summary_bill_url', $first[0]);
        $this->assertArrayHasKey('detail_bill_url', $first[0]);
    }

    public function test_bill_lookup_checks_the_exact_file_instead_of_scanning_a_directory(): void
    {
        $summaryPath = 'monthly_invoices/JAN_2024/M42-JANUARY-2024bill.PDF';
        $detailPath = 'monthly_invoices/JAN_2024/M42-JANUARY-2024billdetail.PDF';
        $this->filesystem->existing = [$summaryPath, $detailPath];

        $summaryUrl = SearchInvoicePdf::getSummaryBillLink('M42', 'Jan 2024');
        $detailUrl = SearchInvoicePdf::getDetailBillLink('M42', 'Jan 2024');

        $this->assertSame([$summaryPath, $detailPath], $this->filesystem->checkedPaths);
        $this->assertSame('route:member.download:M42-JANUARY-2024bill.PDF', $summaryUrl);
        $this->assertSame('route:member.download:M42-JANUARY-2024billdetail.PDF', $detailUrl);
    }
}

class InvoiceArrayCache
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
}

class InvoiceFilesystem
{
    public $checkedPaths = [];
    public $existing = [];

    public function exists($path)
    {
        $this->checkedPaths[] = $path;

        return in_array($path, $this->existing, true);
    }
}

class InvoiceHttpClient
{
    public $options = [];
    public $postCount = 0;
    public $timeoutSeconds;

    public function withoutVerifying()
    {
        return $this;
    }

    public function acceptJson()
    {
        return $this;
    }

    public function withToken($token)
    {
        return $this;
    }

    public function timeout($seconds)
    {
        $this->timeoutSeconds = $seconds;

        return $this;
    }

    public function withOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    public function post($url)
    {
        $this->postCount++;

        return new InvoiceHttpResponse();
    }
}

class InvoiceHttpResponse
{
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
        return [
            'data' => [[
                'Month' => 'Jan 2024',
                'LastBalance' => '100',
                'paidamount' => '25',
                'debitamount' => '10',
                'Balance' => '85',
            ]],
        ];
    }

    public function body()
    {
        return '';
    }
}

class InvoiceUrlGenerator
{
    public function route($name, $parameters = [], $absolute = true)
    {
        return 'route:' . $name . ':' . $parameters['filename'];
    }
}
