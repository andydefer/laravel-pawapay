<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Configs;

use AndyDefer\LaravelPawapay\Callbacks\HandlesCallback;
use AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface;
use AndyDefer\PhpPawapay\Collections\CountryCollection;
use AndyDefer\PhpPawapay\Collections\CurrencyCollection;
use AndyDefer\PhpPawapay\Collections\LanguageCollection;
use AndyDefer\PhpPawapay\Collections\PayerTypeCollection;
use AndyDefer\PhpPawapay\Collections\ProviderCollection;
use AndyDefer\PhpPawapay\Enums\Country;
use AndyDefer\PhpPawapay\Enums\Currency;
use AndyDefer\PhpPawapay\Enums\Language;
use AndyDefer\PhpPawapay\Enums\PawaPayBaseUrl;
use AndyDefer\PhpPawapay\Enums\PayerType;
use AndyDefer\PhpPawapay\Enums\Provider;
use AndyDefer\PhpPawapay\Services\PawapayService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Default implementation of {@see PawapayConfigInterface}.
 *
 * Reads the package configuration from the Laravel config repository and
 * exposes it through typed getters with sensible defaults.
 */
final class PawapayConfig implements PawapayConfigInterface
{
    private const DEFAULT_API_TOKEN = '';

    private const DEFAULT_BASE_URL = PawaPayBaseUrl::SANDBOX;

    private const DEFAULT_SERVICE_FQCN = PawapayService::class;

    private const DEFAULT_HANDLE_CALLBACK_FQCN = HandlesCallback::class;

    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getApiToken(): string
    {
        return (string) $this->config->get(
            'pawapay.api_token',
            self::DEFAULT_API_TOKEN,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getBaseUrl(): PawaPayBaseUrl
    {
        $raw = (string) $this->config->get(
            'pawapay.base_url',
            self::DEFAULT_BASE_URL->value,
        );

        return PawaPayBaseUrl::tryFrom($raw) ?? self::DEFAULT_BASE_URL;
    }

    /**
     * {@inheritDoc}
     */
    public function getServiceFqcn(): string
    {
        return (string) $this->config->get(
            'pawapay.service_fqcn',
            self::DEFAULT_SERVICE_FQCN,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getHandleCallbackFqcn(): string
    {
        return (string) $this->config->get(
            'pawapay.handle_callback_fqcn',
            self::DEFAULT_HANDLE_CALLBACK_FQCN,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrencies(): CurrencyCollection
    {
        return CurrencyCollection::from(
            (array) $this->config->get('pawapay.currencies', Currency::cases()),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getLanguages(): LanguageCollection
    {
        return LanguageCollection::from(
            (array) $this->config->get('pawapay.languages', Language::cases()),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getCountries(): CountryCollection
    {
        return CountryCollection::from(
            (array) $this->config->get('pawapay.countries', Country::cases()),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getProviders(): ProviderCollection
    {
        return ProviderCollection::from(
            (array) $this->config->get('pawapay.providers', Provider::cases()),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getPayerTypes(): PayerTypeCollection
    {
        return PayerTypeCollection::from(
            (array) $this->config->get('pawapay.payer_types', PayerType::cases()),
        );
    }
}
