<?php

namespace Nexxai\LaravelAnalytics;

use Illuminate\Support\ServiceProvider;

class LaravelAnalyticsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'laravel-analytics');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-analytics');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        //         $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/laravel-analytics.php' => config_path('laravel-analytics.php'),
            ], 'config');

            // Publishing the views.
            $this->publishes([
                __DIR__.'/../resources/js/components' => resource_path('js/vendor/laravel-analytics/components'),
            ], 'components');

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/laravel-analytics'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/laravel-analytics'),
            ], 'lang');*/

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-analytics.php', 'laravel-analytics');

        // Register the main class to use with the facade
        $this->app->singleton('laravel-analytics', function () {
            return new LaravelAnalytics;
        });
    }
}
