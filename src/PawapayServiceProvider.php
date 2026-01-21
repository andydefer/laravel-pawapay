<?php

declare(strict_types=1);

namespace Pawapay;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Pawapay\Commands\GenerateTypesCommand;
use Pawapay\Contracts\PawapayClientInterface;
use Pawapay\Data\PawapayConfigData;
use Pawapay\Services\PawapayClient;
use Pawapay\Services\PawapayService;
use Pawapay\Services\TypesGeneratorService;

class PawapayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/pawapay.php',
            'pawapay'
        );

        // Register the client
        $this->app->singleton(PawapayClientInterface::class, function (Application $app): PawapayClient {
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
        $this->app->singleton(PawapayService::class, function (Application $app): PawapayService {
            return new PawapayService(
                $app->make(PawapayClientInterface::class)
            );
        });

        // Register types generator service
        $this->app->singleton(TypesGeneratorService::class, function (): TypesGeneratorService {
            return new TypesGeneratorService();
        });

        // Create aliases for convenient access
        $this->app->alias(PawapayService::class, 'pawapay');
        $this->app->alias(PawapayClientInterface::class, 'pawapay.client');
        $this->app->alias(TypesGeneratorService::class, 'pawapay.types-generator');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateTypesCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/pawapay.php' => config_path('pawapay.php'),
            ], 'pawapay-config');

            $this->publishes([
                __DIR__ . '/../stubs/typescript' => resource_path('js/pawapay'),
            ], 'pawapay-types');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            PawapayClientInterface::class,
            PawapayService::class,
            TypesGeneratorService::class,
            'pawapay',
            'pawapay.client',
            'pawapay.types-generator',
        ];
    }
}
