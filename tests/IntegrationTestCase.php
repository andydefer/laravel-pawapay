<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Tests;

use AndyDefer\Actions\ActionServiceProvider;
use AndyDefer\LaravelPawapay\PawapayServiceProvider;
use AndyDefer\PhpPawapay\Contracts\PawapayClientInterface;
use AndyDefer\PhpPawapay\PawapayClient;
use AndyDefer\PhpPawapay\Services\PawapayService;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class IntegrationTestCase extends Orchestra
{
    protected MockPawapayClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        Route::clearResolvedInstances();
        $this->app['router']->getRoutes()->refreshNameLookups();

        $this->client = new MockPawapayClient('test-token');

        $this->app->instance(PawapayClient::class, $this->client);
        $this->app->instance(PawapayClientInterface::class, $this->client);

        $this->app->bind(PawapayService::class, function ($app): PawapayService {
            return new PawapayService(
                $app->make(PawapayClientInterface::class),
            );
        });
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        \Mockery::close();
    }

    protected function getPackageProviders($app): array
    {
        return [
            PawapayServiceProvider::class,
            ActionServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('pawapay.api_token', 'test-token');
        $app['config']->set('pawapay.base_url', 'https://api.sandbox.pawapay.io/');
    }
}
