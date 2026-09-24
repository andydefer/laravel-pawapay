<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Tests\Integration\Providers;

use AndyDefer\LaravelPawapay\Configs\PawapayConfig;
use AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface;
use AndyDefer\LaravelPawapay\PawapayServiceProvider;
use AndyDefer\LaravelPawapay\Tests\Fixtures\Services\CustomPawapayService;
use AndyDefer\LaravelPawapay\Tests\IntegrationTestCase;
use AndyDefer\PhpPawapay\Contracts\PawapayClientInterface;
use AndyDefer\PhpPawapay\Contracts\PawapayInterface;
use AndyDefer\PhpPawapay\Enums\PawaPayBaseUrl;
use AndyDefer\PhpPawapay\PawapayClient;
use AndyDefer\PhpPawapay\Services\PawapayService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\ServiceProvider;

final class PawapayServiceProviderTest extends IntegrationTestCase
{
    // ============================================================
    // CONFIG
    // ============================================================

    public function test_it_registers_pawapay_config_as_singleton(): void
    {
        $first = $this->app->make(PawapayConfig::class);
        $second = $this->app->make(PawapayConfig::class);

        $this->assertInstanceOf(PawapayConfig::class, $first);
        $this->assertSame($first, $second);
    }

    public function test_it_binds_pawapay_config_interface_to_default_implementation(): void
    {
        $config = $this->app->make(PawapayConfigInterface::class);

        $this->assertInstanceOf(PawapayConfig::class, $config);
    }

    public function test_it_merges_default_configuration(): void
    {
        /** @var ConfigRepository $config */
        $config = $this->app->make(ConfigRepository::class);

        $this->assertNotNull($config->get('pawapay.environment'));
        $this->assertIsArray($config->get('pawapay.tokens'));
        $this->assertArrayHasKey('sandbox', $config->get('pawapay.tokens'));
        $this->assertArrayHasKey('production', $config->get('pawapay.tokens'));
        $this->assertNotNull($config->get('pawapay.service_fqcn'));
        $this->assertNotNull($config->get('pawapay.handle_callback_fqcn'));
        $this->assertNotNull($config->get('pawapay.currencies'));
        $this->assertNotNull($config->get('pawapay.languages'));
        $this->assertNotNull($config->get('pawapay.countries'));
        $this->assertNotNull($config->get('pawapay.providers'));
        $this->assertNotNull($config->get('pawapay.payer_types'));
    }

    public function test_it_uses_sandbox_token_when_environment_is_sandbox(): void
    {
        config()->set('pawapay.environment', PawaPayBaseUrl::SANDBOX->value);
        config()->set('pawapay.tokens.sandbox', 'sandbox-token-123');
        config()->set('pawapay.tokens.production', 'production-token-456');

        $config = $this->app->make(PawapayConfigInterface::class);

        $this->assertSame(PawaPayBaseUrl::SANDBOX, $config->getEnvironment());
        $this->assertSame('sandbox-token-123', $config->getApiToken());
    }

    public function test_it_uses_production_token_when_environment_is_production(): void
    {
        config()->set('pawapay.environment', PawaPayBaseUrl::PRODUCTION->value);
        config()->set('pawapay.tokens.sandbox', 'sandbox-token-123');
        config()->set('pawapay.tokens.production', 'production-token-456');

        $config = $this->app->make(PawapayConfigInterface::class);

        $this->assertSame(PawaPayBaseUrl::PRODUCTION, $config->getEnvironment());
        $this->assertSame('production-token-456', $config->getApiToken());
    }

    // ============================================================
    // CLIENT
    // ============================================================

    public function test_it_registers_pawapay_client_as_singleton(): void
    {
        $first = $this->app->make(PawapayClient::class);
        $second = $this->app->make(PawapayClient::class);

        $this->assertInstanceOf(PawapayClient::class, $first);
        $this->assertSame($first, $second);
    }

    public function test_it_binds_pawapay_client_interface_to_default_implementation(): void
    {
        $client = $this->app->make(PawapayClientInterface::class);

        $this->assertInstanceOf(PawapayClient::class, $client);
    }

    public function test_it_builds_client_with_config_token_and_base_url(): void
    {
        config()->set('pawapay.environment', PawaPayBaseUrl::SANDBOX->value);
        config()->set('pawapay.tokens.sandbox', 'custom-token-abc');

        // Force re-resolution so the singleton is built with the new config.
        $this->app->forgetInstance(PawapayClient::class);

        $client = $this->app->make(PawapayClient::class);

        $reflection = new \ReflectionClass($client);
        $apiToken = $reflection->getProperty('apiToken');
        $apiToken->setAccessible(true);
        $baseUrl = $reflection->getProperty('baseUrl');
        $baseUrl->setAccessible(true);

        $this->assertSame('custom-token-abc', $apiToken->getValue($client));
        $this->assertSame(PawaPayBaseUrl::SANDBOX, $baseUrl->getValue($client));
    }

    // ============================================================
    // SERVICE
    // ============================================================

    public function test_it_registers_pawapay_service_as_singleton(): void
    {
        $first = $this->app->make(PawapayService::class);
        $second = $this->app->make(PawapayService::class);

        $this->assertInstanceOf(PawapayService::class, $first);
        $this->assertEquals($first, $second);
    }

    public function test_it_binds_pawapay_interface_to_configured_service(): void
    {
        $service = $this->app->make(PawapayInterface::class);

        $this->assertInstanceOf(PawapayService::class, $service);
    }

    public function test_it_resolves_custom_service_from_config(): void
    {
        config()->set('pawapay.service_fqcn', CustomPawapayService::class);

        $service = $this->app->make(PawapayInterface::class);

        $this->assertInstanceOf(CustomPawapayService::class, $service);
    }

    public function test_it_injects_client_interface_into_service(): void
    {
        $service = $this->app->make(PawapayService::class);

        $reflection = new \ReflectionClass($service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);

        $this->assertInstanceOf(PawapayClientInterface::class, $clientProperty->getValue($service));
    }

    // ============================================================
    // BOOT
    // ============================================================

    public function test_it_publishes_config_when_running_in_console(): void
    {
        $paths = ServiceProvider::pathsToPublish(
            PawapayServiceProvider::class,
            'laravel-pawapay-config',
        );

        $this->assertNotEmpty($paths);
        $this->assertContains(config_path('pawapay.php'), array_values($paths));
    }

    public function test_it_publishes_routes_when_running_in_console(): void
    {
        $paths = ServiceProvider::pathsToPublish(
            PawapayServiceProvider::class,
            'laravel-pawapay-routes',
        );

        $this->assertNotEmpty($paths);
        $this->assertContains(base_path('routes/pawapay.php'), array_values($paths));
    }

    // ============================================================
    // FULL PIPELINE
    // ============================================================

    public function test_full_resolution_chain_returns_working_service(): void
    {
        config()->set('pawapay.environment', PawaPayBaseUrl::SANDBOX->value);
        config()->set('pawapay.tokens.sandbox', 'pipeline-token');

        $this->app->forgetInstance(PawapayClient::class);
        $this->app->forgetInstance(PawapayService::class);

        $service = $this->app->make(PawapayInterface::class);

        $this->assertInstanceOf(PawapayService::class, $service);

        $reflection = new \ReflectionClass($service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);

        /** @var PawapayClient $client */
        $client = $clientProperty->getValue($service);

        $this->assertInstanceOf(PawapayClient::class, $client);
    }
}
