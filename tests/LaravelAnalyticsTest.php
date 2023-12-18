<?php

namespace WdevRs\LaravelAnalytics\Tests;

use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Route;
use WdevRs\LaravelAnalytics\Http\Middleware\Analytics;
use WdevRs\LaravelAnalytics\LaravelAnalyticsServiceProvider;
use WdevRs\LaravelAnalytics\Models\PageView;

class LaravelAnalyticsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Process::fake([
            '*' => Process::result(
                output: file_get_contents(getcwd().'/tests/fixtures/whois.txt'),
            ),
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [LaravelAnalyticsServiceProvider::class];
    }

    public function testPageViewTracing()
    {
        $request = Request::create('/test/path', 'GET');

        (new Analytics())->handle($request, function ($req) {
            $this->assertEquals('test/path', $req->path());
            $this->assertEquals('GET', $req->method());
        });

        $this->assertCount(1, PageView::all());
        $this->assertDatabaseHas(app(PageView::class)->getTable(), [
            'path' => 'test/path',
        ]);
    }

    public function testPageViewTracingWithModel()
    {
        Route::get('test/path/{pageView}', function (PageView $pageView) {
            return 'Test path';
        })->middleware([SubstituteBindings::class, Analytics::class]);

        $pageView = PageView::factory()->create([
            'path' => 'tp',
        ]);

        $this->get('test/path/'.$pageView->getKey());

        $this->assertCount(2, PageView::all());
        $this->assertDatabaseHas(app(PageView::class)->getTable(), [
            'path' => 'test/path/1',
        ]);
    }

    public function testPageViewTracingWithNonModelRouteParam()
    {
        Route::get('test/path/{any}', function (int $any) {
            return 'Test path';
        })->middleware([SubstituteBindings::class, Analytics::class]);

        $this->get('test/path/1');

        $this->assertCount(1, PageView::all());
        $this->assertDatabaseHas(app(PageView::class)->getTable(), [
            'path' => 'test/path/1',
        ]);
    }

    public function testItFiltersOutBotTraffic()
    {
        Route::get('test/path/{any}', function (int $any) {
            return 'Test path';
        })->middleware([SubstituteBindings::class, Analytics::class]);

        $this->get('test/path/1', ['User-Agent' => 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.5563.146 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)']);

        $this->assertCount(0, PageView::all());
        $this->assertDatabaseMissing(app(PageView::class)->getTable(), [
            'path' => 'test/path/1',
        ]);
    }

    public function testSavesIpWithoutProxy()
    {
        $this->fakeProcessLocal();

        $request = Request::create('/test/path', 'GET');

        $request->server->set('REMOTE_ADDR', '10.0.0.1');

        (new Analytics())->handle($request, function ($req) {
            $this->assertEquals('test/path', $req->path());
            $this->assertEquals('GET', $req->method());
        });

        $this->assertCount(1, PageView::all());
        $this->assertDatabaseHas(app(PageView::class)->getTable(), [
            'cidr' => 'UNKNOWN',
            'path' => 'test/path',
        ]);
    }

    public function testSavesIpWithProxy()
    {
        $this->fakeProcessLocal();

        $request = Request::create('/test/path', 'GET');

        $request->headers->set('X-Forwarded-For', '10.0.0.1');

        (new Analytics())->handle($request, function ($req) {
            $this->assertEquals('test/path', $req->path());
            $this->assertEquals('GET', $req->method());
        });

        $this->assertCount(1, PageView::all());
        $this->assertDatabaseHas(app(PageView::class)->getTable(), [
            'cidr' => 'UNKNOWN',
            'path' => 'test/path',
        ]);
    }

    public function testSavesCountryWithProxy()
    {
        $request = Request::create('/test/path', 'GET');

        $request->server->set('REMOTE_ADDR', '162.158.166.31');

        (new Analytics())->handle($request, function ($req) {
            $this->assertEquals('test/path', $req->path());
            $this->assertEquals('GET', $req->method());
        });

        $this->assertCount(1, PageView::all());

        $this->assertDatabaseHas(app(PageView::class)->getTable(), [
            'cidr' => '162.158.0.0/15',
            'country' => 'US',
        ]);
    }

    public function testCanGetCIDR()
    {
        $analytics = new \WdevRs\LaravelAnalytics\Http\Middleware\Analytics;

        $cidr = $analytics->getCidr('162.158.166.31');

        $this->assertEquals('162.158.0.0/15', $cidr);
    }

    public function testCanWhoisAnIP()
    {
        $analytics = new \WdevRs\LaravelAnalytics\Http\Middleware\Analytics;

        $lookup = $analytics->ipWhois('162.158.166.31');

        $this->assertContains('NetName:        CLOUDFLARENET', $lookup);
    }

    public function testCanGetCountryFromIP()
    {
        $analytics = new \WdevRs\LaravelAnalytics\Http\Middleware\Analytics;

        $lookup = $analytics->getCountry('162.158.166.31');

        $this->assertEquals('US', $lookup);
    }

    public function testIfCountryIsUnknown()
    {
        $this->fakeProcessLocal();

        $analytics = new \WdevRs\LaravelAnalytics\Http\Middleware\Analytics;

        $lookup = $analytics->getCountry('10.0.0.1');

        $this->assertEquals('UNKNOWN', $lookup);
    }

    public function testIfIPIsUnknown()
    {
        $this->fakeProcessLocal();

        $analytics = new \WdevRs\LaravelAnalytics\Http\Middleware\Analytics;

        $lookup = $analytics->getCidr('10.0.0.1');

        $this->assertEquals('UNKNOWN', $lookup);
    }

    public function fakeProcessLocal()
    {
        Process::fake([
            '*' => Process::result(
                output: file_get_contents(getcwd().'/tests/fixtures/localWhois.txt'),
            ),
        ]);
    }
}
