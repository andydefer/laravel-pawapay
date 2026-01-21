<?php

declare(strict_types=1);

namespace Pawapay;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Pawapay\Commands\GenerateTypesCommand;
use Pawapay\Commands\InstallPawapayCommand;
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
        $this->registerRoutes();
        $this->registerPublishing();
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        // Si les routes personnalisées existent (publiées par l'utilisateur), on les charge
        $customRoutesPath = base_path('routes/pawapay.php');

        if (file_exists($customRoutesPath)) {
            $this->loadRoutesFrom($customRoutesPath);
            return;
        }

        // Sinon, on charge les routes par défaut du package
        $this->loadRoutesFrom(__DIR__ . '/routes/pawapay.php');
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateTypesCommand::class,
                InstallPawapayCommand::class,
            ]);

            // Configuration
            $this->publishes([
                __DIR__ . '/../config/pawapay.php' => config_path('pawapay.php'),
            ], 'pawapay-config');

            // Controller (publication du stub transformé en fichier PHP)
            $this->publishes([
                __DIR__ . '/stubs/controllers/PawapayController.stub' => app_path('Http/Controllers/Api/PawapayController.php'),
            ], 'pawapay-controller');

            // Routes personnalisées (optionnel)
            $this->publishes([
                __DIR__ . '/stubs/routes/pawapay-routes.stub' => base_path('routes/pawapay.php'),
            ], 'pawapay-routes');

            // NOTE: On ne publie plus les stubs TypeScript
            // Les fichiers TypeScript sont générés directement par la commande
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
