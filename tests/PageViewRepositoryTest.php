<?php

uses(\WdevRs\LaravelAnalytics\Tests\TestCase::class);
use Illuminate\Support\Carbon;
use WdevRs\LaravelAnalytics\Models\PageView;
use WdevRs\LaravelAnalytics\Repositories\PageViewRepository;


test('it can get page view data by date', function () {
    $pageViewRepository =  app(PageViewRepository::class);

    expect($pageViewRepository)->not->toBeNull();

    PageView::factory()->count(10)->create(
        [
            'created_at' => Carbon::today()->subWeeks(3)
        ]
    );

    PageView::factory()->count(10)->create(
        [
            'created_at' => Carbon::today()->subWeeks(5)
        ]
    );

    $analyticsData = $pageViewRepository->getByDate(Carbon::today()->subWeeks(4));

    expect($analyticsData)->toHaveCount(10);
});

test('it can get page view data by date groupped by path', function () {
    $pageViewRepository =  app(PageViewRepository::class);

    expect($pageViewRepository)->not->toBeNull();

    PageView::factory()->count(10)->create(
        [
            'path' => 'test/1',
            'created_at' => Carbon::today()->subWeeks(3)
        ]
    );

    PageView::factory()->count(5)->create(
        [
            'path' => 'test/2',
            'created_at' => Carbon::today()->subWeeks(3)
        ]
    );

    $analyticsData = $pageViewRepository->getByDateGroupedByPath(Carbon::today()->subWeeks(4));
    expect($analyticsData)->toHaveCount(2);
    expect($analyticsData['test/1'])->toEqual(10);
    expect($analyticsData['test/2'])->toEqual(5);
});

test('it can get page view data by date groupped by days', function () {
    $pageViewRepository =  app(PageViewRepository::class);

    expect($pageViewRepository)->not->toBeNull();

    PageView::factory()->count(10)->create(
        [
            'path' => 'test/1',
            'created_at' => Carbon::today()->subDays(1)
        ]
    );

    PageView::factory()->count(5)->create(
        [
            'path' => 'test/1',
            'created_at' => Carbon::today()->subDays(2)
        ]
    );

    $analyticsData = $pageViewRepository->getByDateGroupedByDays(Carbon::today()->subWeeks(4));

    expect($analyticsData)->toHaveCount(2);
    expect($analyticsData[Carbon::today()->subDays(1)->toDateString()])->toEqual(10);
    expect($analyticsData[Carbon::today()->subDays(2)->toDateString()])->toEqual(5);
});

test('it can get visitors by date groupped by days', function () {
    /** @var PageViewRepository $pageViewRepository */
    $pageViewRepository =  app(PageViewRepository::class);

    expect($pageViewRepository)->not->toBeNull();

    PageView::factory()->count(10)->create(
        [
            'session_id' => 'session1',
            'path' => 'test/1',
            'created_at' => Carbon::today()->subDays(1)
        ]
    );

    PageView::factory()->count(5)->create(
        [
            'session_id' => 'session2',
            'path' => 'test/1',
            'created_at' => Carbon::today()->subDays(2)
        ]
    );

    PageView::factory()->count(5)->create(
        [
            'session_id' => 'session3',
            'path' => 'test/4',
            'created_at' => Carbon::today()->subDays(2)
        ]
    );

    $analyticsData = $pageViewRepository->getVisitorsByDateGroupedByDays(Carbon::today()->subWeeks(4));
    expect($analyticsData)->toHaveCount(2);
    expect($analyticsData[Carbon::today()->subDays(1)->toDateString()])->toEqual(1);
    expect($analyticsData[Carbon::today()->subDays(2)->toDateString()])->toEqual(2);
});
