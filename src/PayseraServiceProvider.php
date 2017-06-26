<?php

namespace Antcern\Paysera;

use Illuminate\Support\ServiceProvider;

class PayseraServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('paysera.php')
        ]);
    }

    /**
     * Register the application services.
     *
     *
     *
     * @return void
     */
    public function register()
    {
        require_once(__DIR__.'/../lib/WebToPay.php');

        $this->app->singleton('paysera', function() {
            return new PayseraManager();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['paysera'];
    }
}
