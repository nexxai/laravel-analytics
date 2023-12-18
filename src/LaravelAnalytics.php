<?php

namespace Nexxai\LaravelAnalytics;

use Illuminate\Support\Facades\Route;
use Nexxai\LaravelAnalytics\Http\Controllers\AnalyticsController;

class LaravelAnalytics
{
    public static function routes()
    {

        Route::get(
            'analytics/page-views-per-days',
            [AnalyticsController::class, 'getPageViewsPerDays']
        );

        Route::get(
            'analytics/page-views-per-path',
            [AnalyticsController::class, 'getPageViewsPerPaths']
        );
    }
}
