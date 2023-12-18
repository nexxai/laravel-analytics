<?php

uses(\Nexxai\LaravelAnalytics\Tests\TestCase::class);
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Route;
use Nexxai\LaravelAnalytics\Http\Middleware\Analytics;
use Nexxai\LaravelAnalytics\LaravelAnalyticsServiceProvider;
use Nexxai\LaravelAnalytics\Models\PageView;

beforeEach(function () {
    Process::fake([
        '*' => Process::result(
            output: file_get_contents(getcwd().'/tests/fixtures/whois.txt'),
        ),
    ]);
});

function getPackageProviders($app): array
{
    return [LaravelAnalyticsServiceProvider::class];
}

test('page view tracing', function () {
    $request = Request::create('/test/path', 'GET');

    (new Analytics())->handle($request, function ($req) {
        expect($req->path())->toEqual('test/path');
        expect($req->method())->toEqual('GET');
    });

    expect(PageView::all())->toHaveCount(1);
    $this->assertDatabaseHas(app(PageView::class)->getTable(), [
        'path' => 'test/path',
    ]);
});

test('page view tracing with model', function () {
    Route::get('test/path/{pageView}', function (PageView $pageView) {
        return 'Test path';
    })->middleware([SubstituteBindings::class, Analytics::class]);

    $pageView = PageView::factory()->create([
        'path' => 'tp',
    ]);

    $this->get('test/path/'.$pageView->getKey());

    expect(PageView::all())->toHaveCount(2);
    $this->assertDatabaseHas(app(PageView::class)->getTable(), [
        'path' => 'test/path/1',
    ]);
});

test('page view tracing with non model route param', function () {
    Route::get('test/path/{any}', function (int $any) {
        return 'Test path';
    })->middleware([SubstituteBindings::class, Analytics::class]);

    $this->get('test/path/1');

    expect(PageView::all())->toHaveCount(1);
    $this->assertDatabaseHas(app(PageView::class)->getTable(), [
        'path' => 'test/path/1',
    ]);
});

test('it filters out bot traffic', function () {
    Route::get('test/path/{any}', function (int $any) {
        return 'Test path';
    })->middleware([SubstituteBindings::class, Analytics::class]);

    $this->get('test/path/1', ['User-Agent' => 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.5563.146 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)']);

    expect(PageView::all())->toHaveCount(0);
    $this->assertDatabaseMissing(app(PageView::class)->getTable(), [
        'path' => 'test/path/1',
    ]);
});

test('saves ip without proxy', function () {
    fakeProcessLocal();

    $request = Request::create('/test/path', 'GET');

    $request->server->set('REMOTE_ADDR', '10.0.0.1');

    (new Analytics())->handle($request, function ($req) {
        expect($req->path())->toEqual('test/path');
        expect($req->method())->toEqual('GET');
    });

    expect(PageView::all())->toHaveCount(1);
    $this->assertDatabaseHas(app(PageView::class)->getTable(), [
        'cidr' => 'UNKNOWN',
        'path' => 'test/path',
    ]);
});

test('saves ip with proxy', function () {
    fakeProcessLocal();

    $request = Request::create('/test/path', 'GET');

    $request->headers->set('X-Forwarded-For', '10.0.0.1');

    (new Analytics())->handle($request, function ($req) {
        expect($req->path())->toEqual('test/path');
        expect($req->method())->toEqual('GET');
    });

    expect(PageView::all())->toHaveCount(1);
    $this->assertDatabaseHas(app(PageView::class)->getTable(), [
        'cidr' => 'UNKNOWN',
        'path' => 'test/path',
    ]);
});

test('saves country with proxy', function () {
    $request = Request::create('/test/path', 'GET');

    $request->server->set('REMOTE_ADDR', '162.158.166.31');

    (new Analytics())->handle($request, function ($req) {
        expect($req->path())->toEqual('test/path');
        expect($req->method())->toEqual('GET');
    });

    expect(PageView::all())->toHaveCount(1);

    $this->assertDatabaseHas(app(PageView::class)->getTable(), [
        'cidr' => '162.158.0.0/15',
        'country' => 'US',
    ]);
});

test('can get c i d r', function () {
    $analytics = new \Nexxai\LaravelAnalytics\Http\Middleware\Analytics;

    $cidr = $analytics->getCidr('162.158.166.31');

    expect($cidr)->toEqual('162.158.0.0/15');
});

test('can whois an i p', function () {
    $analytics = new \Nexxai\LaravelAnalytics\Http\Middleware\Analytics;

    $lookup = $analytics->ipWhois('162.158.166.31');

    expect($lookup)->toContain('NetName:        CLOUDFLARENET');
});

test('can get country from i p', function () {
    $analytics = new \Nexxai\LaravelAnalytics\Http\Middleware\Analytics;

    $lookup = $analytics->getCountry('162.158.166.31');

    expect($lookup)->toEqual('US');
});

test('if country is unknown', function () {
    fakeProcessLocal();

    $analytics = new \Nexxai\LaravelAnalytics\Http\Middleware\Analytics;

    $lookup = $analytics->getCountry('10.0.0.1');

    expect($lookup)->toEqual('UNKNOWN');
});

test('if i p is unknown', function () {
    fakeProcessLocal();

    $analytics = new \Nexxai\LaravelAnalytics\Http\Middleware\Analytics;

    $lookup = $analytics->getCidr('10.0.0.1');

    expect($lookup)->toEqual('UNKNOWN');
});

function fakeProcessLocal()
{
    Process::fake([
        '*' => Process::result(
            output: file_get_contents(getcwd().'/tests/fixtures/localWhois.txt'),
        ),
    ]);
}
