<?php

namespace Nexxai\LaravelAnalytics;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Nexxai\LaravelAnalytics\LaravelAnalytics
 */
class Analytics extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-analytics';
    }
}
