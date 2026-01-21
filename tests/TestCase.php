<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Pawapay\PawapayServiceProvider;

/**
 * Base test case for Roster package tests.
 *
 * Provides common setup for all package tests including:
 * - Observer registration for domain models
 * - Database migrations loading
 * - Test environment configuration
 */
abstract class TestCase extends OrchestraTestCase
{
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPackageMigrations();
        $this->loadTestMigrations();
        $this->configureMemoryCache();
        $this->configurePawapayForTests();
    }

    /**
     * Load package migrations.
     */
    private function loadPackageMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Load test-specific migrations.
     */
    private function loadTestMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    /**
     * Configure in-memory cache for tests.
     */
    private function configureMemoryCache(): void
    {
        Config::set('cache.default', 'array');
    }

    /**
     * Configure PawaPay for integration tests.
     */
    private function configurePawapayForTests(): void
    {
        // Configuration pour les tests d'intégration
        Config::set('pawapay.environment', 'sandbox');
        Config::set('pawapay.api.sandbox_url', 'https://api.sandbox.pawapay.io/v2');
        Config::set('pawapay.api.token', 'eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ.eyJ0dCI6IkFBVCIsInN1YiI6Ijk4ODgiLCJtYXYiOiIxIiwiZXhwIjoyMDg0NDQwNTEyLCJpYXQiOjE3Njg5MDc3MTIsInBtIjoiREFGLFBBRiIsImp0aSI6ImQyYTIwMjc2LTczYTUtNDM5ZC05MGRkLWU5ZDEyNDM2MTUxNCJ9.IMHShHmzDkiINPhbdOlFMVw0z5p56TQNmMTPqPq3OuW8SEb8kAtzirZhN9plsxxsK-J6IyqO4i3yCff65PE0MA');
        Config::set('pawapay.api.timeout', 30);
        Config::set('pawapay.api.retry_times', 3);
        Config::set('pawapay.api.retry_sleep', 100);
        Config::set('pawapay.defaults.headers', [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
    }

    /**
     * Get package service providers.
     *
     * @param mixed $app
     */
    protected function getPackageProviders($app): array
    {
        return [
            PawapayServiceProvider::class,
        ];
    }

    /**
     * Define the test environment configuration.
     *
     * @param mixed $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
