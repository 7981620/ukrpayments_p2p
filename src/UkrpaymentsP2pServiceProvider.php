<?php

namespace Agenta\UkrpaymentsP2p;

use Illuminate\Support\ServiceProvider;

class UkrpaymentsP2pServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'ukrpayments_p2p');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ukrpayments_p2p');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('ukrpayments_p2p.php'),
            ], 'config');

            // Publishing the views.
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/ukrpayments_p2p'),
            ], 'views-ukrpayments_p2p');

            // Publishing assets.
            $this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/ukrpayments_p2p'),
            ], 'assets-ukrpayments_p2p');

            // Publishing the translation files.
            $this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/ukrpayments_p2p'),
            ], 'lang-ukrpayments_p2p');

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
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'ukrpayments_p2p');

        // Register the main class to use with the facade
        $this->app->singleton('ukrpayments_p2p', function () {
            return new UkrpaymentsP2p;
        });
    }
}
