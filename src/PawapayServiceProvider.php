<?php

declare(strict_types=1);

namespace Pawapay;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Pawapay\Contracts\PawapayClientInterface;
use Pawapay\Data\PawapayConfigData;
use Pawapay\Services\PawapayClient;
use Pawapay\Services\PawapayService;
use Pawapay\Services\PawapayPaymentPageService;

class PawapayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/pawapay.php',
            'pawapay'
        );

        // Register the client
        $this->app->singleton(PawapayClientInterface::class, function (Application $app) {
            $config = $app->make('config');

            $configData = new PawapayConfigData(
                sandboxUrl: $config->get('pawapay.api.sandbox_url'),
                productionUrl: $config->get('pawapay.api.production_url'),
                token: $config->get('pawapay.api.token'),
                timeout: (int) $config->get('pawapay.api.timeout', 30),
                retryTimes: (int) $config->get('pawapay.api.retry_times', 3),
                retrySleep: (int) $config->get('pawapay.api.retry_sleep', 100),
                environment: $config->get('pawapay.environment', 'sandbox'),
                defaultHeaders: $config->get('pawapay.defaults.headers', [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]),
            );

            return new PawapayClient($configData);
        });

        // Register the main service
        $this->app->singleton(PawapayService::class, function (Application $app) {
            return new PawapayService(
                $app->make(PawapayClientInterface::class)
            );
        });

        // Register the payment page service
        $this->app->singleton(PawapayPaymentPageService::class, function (Application $app) {
            return new PawapayPaymentPageService(
                $app->make(PawapayClientInterface::class)
            );
        });

        // Create aliases for convenient access
        $this->app->alias(PawapayService::class, 'pawapay');
        $this->app->alias(PawapayPaymentPageService::class, 'pawapay.payment-page');
        $this->app->alias(PawapayClientInterface::class, 'pawapay.client');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/pawapay.php' => config_path('pawapay.php'),
            ], 'pawapay-config');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            PawapayClientInterface::class,
            PawapayService::class,
            PawapayPaymentPageService::class,
            'pawapay',
            'pawapay.payment-page',
            'pawapay.client'
        ];
    }
}
