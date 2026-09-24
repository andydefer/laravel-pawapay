<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay;

use AndyDefer\LaravelPawapay\Configs\PawapayConfig;
use AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface;
use AndyDefer\PhpPawapay\Contracts\PawapayClientInterface;
use AndyDefer\PhpPawapay\Contracts\PawapayInterface;
use AndyDefer\PhpPawapay\PawapayClient;
use AndyDefer\PhpPawapay\Services\PawapayService;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the PawaPay configuration, HTTP client, and service into the
 * Laravel container.
 *
 * The concrete service bound to {@see PawapayInterface} is resolved from
 * the `pawapay.service_fqcn` configuration key, allowing host applications
 * to swap the implementation without touching the package.
 */
final class PawapayServiceProvider extends ServiceProvider
{
    /**
     * Register container bindings.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/pawapay.php',
            'pawapay',
        );

        $this->registerConfig();
        $this->registerClient();
        $this->registerService();
    }

    /**
     * Publish the configuration and routes for the host application.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/pawapay.php' => config_path('pawapay.php'),
        ], 'laravel-pawapay-config');

        $this->publishes([
            __DIR__.'/../routes/api.php' => base_path('routes/pawapay.php'),
        ], 'laravel-pawapay-routes');
    }

    /**
     * Bind the package configuration.
     */
    private function registerConfig(): void
    {
        $this->app->singleton(PawapayConfig::class, function ($app): PawapayConfig {
            return new PawapayConfig(
                $app['config'],
            );
        });

        $this->app->bind(PawapayConfigInterface::class, PawapayConfig::class);
    }

    /**
     * Bind the low-level PawaPay HTTP client.
     */
    private function registerClient(): void
    {
        $this->app->singleton(PawapayClient::class, function ($app): PawapayClient {
            /** @var PawapayConfigInterface $config */
            $config = $app->make(PawapayConfigInterface::class);

            return new PawapayClient(
                apiToken: $config->getApiToken(),
                baseUrl: $config->getBaseUrl(),
            );
        });

        $this->app->bind(PawapayClientInterface::class, PawapayClient::class);
    }

    /**
     * Bind the application-level PawaPay service.
     *
     * The concrete class is resolved from the configuration, so the host
     * application can substitute its own implementation.
     */
    private function registerService(): void
    {
        $this->app->singleton(PawapayService::class, function ($app): PawapayService {
            return new PawapayService(
                client: $app->make(PawapayClientInterface::class),
            );
        });

        $this->app->bind(PawapayInterface::class, function ($app): PawapayInterface {
            /** @var PawapayConfigInterface $config */
            $config = $app->make(PawapayConfigInterface::class);

            return $app->make($config->getServiceFqcn());
        });
    }
}
