<?php

declare(strict_types=1);

namespace PawaPay;

use Illuminate\Support\ServiceProvider;
use PawaPay\Services\PawaPayClient;

class PawaPayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/pawapay.php',
            'pawapay'
        );

        // Bind main client
        $this->app->singleton('pawapay', function () {
            return new PawaPayClient(
                config('pawapay.api_key'),
                config('pawapay.base_url'),
                config('pawapay.timeout')
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/pawapay.php' => config_path('pawapay.php'),
        ], 'pawapay-config');
    }
}
