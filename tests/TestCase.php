<?php

namespace Nexxai\LaravelAnalytics\Tests;

use Nexxai\LaravelAnalytics\LaravelAnalyticsServiceProvider;

/**
 * Class TestCase base Class for test cases
 */
abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--database' => 'testing'])->run();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelAnalyticsServiceProvider::class,
        ];
    }
}
