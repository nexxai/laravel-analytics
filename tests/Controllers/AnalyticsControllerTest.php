<?php

uses(\WdevRs\LaravelAnalytics\Tests\TestCase::class);
use Mockery\Mock;
use Mockery\MockInterface;
use WdevRs\LaravelAnalytics\LaravelAnalytics;
use WdevRs\LaravelAnalytics\Repositories\PageViewRepository;

beforeEach(function () {
    LaravelAnalytics::routes();
});

test('it can get page views per day', function () {
    $this->mock(PageViewRepository::class, function (MockInterface $mock){
       $mock->shouldReceive('getByDateGroupedByDays')->andReturn(
           collect([
               '2023-03-28' => 2,
               '2023-03-29' => 5
            ])
       );
    });

    $response = $this->getJson('analytics/page-views-per-days')->assertOk();

    expect($response->getData()->{'2023-03-28'})->toEqual(2);
    expect($response->getData()->{'2023-03-29'})->toEqual(5);
});

test('it can get page views per paths', function () {
    $this->mock(PageViewRepository::class, function (MockInterface $mock){
        $mock->shouldReceive('getByDateGroupedByPath')->andReturn(
            collect([
                'test/1' => 2,
                'test/2' => 5
            ])
        );
    });

    $response = $this->getJson('analytics/page-views-per-path')->assertOk();

    expect($response->getData()->{'test/1'})->toEqual(2);
    expect($response->getData()->{'test/2'})->toEqual(5);
});
