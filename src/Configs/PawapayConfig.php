<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Configs;

use AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface;
use AndyDefer\PhpPawapay\Collections\CountryCollection;
use AndyDefer\PhpPawapay\Collections\CurrencyCollection;
use AndyDefer\PhpPawapay\Collections\LanguageCollection;
use AndyDefer\PhpPawapay\Enums\Country;
use AndyDefer\PhpPawapay\Enums\Currency;
use AndyDefer\PhpPawapay\Enums\Language;
use AndyDefer\PhpPawapay\Enums\PawaPayBaseUrl;
use AndyDefer\PhpPawapay\Services\PawapayService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Default implementation of {@see PawapayConfigInterface}.
 *
 * Reads every PawaPay setting from the Laravel config repository under the
 * `pawapay` namespace, applies sensible fallbacks when a key is missing, and
 * exposes typed collections for currencies, languages and countries.
 */
final class PawapayConfig implements PawapayConfigInterface
{
    private const DEFAULT_API_TOKEN = '';

    private const DEFAULT_BASE_URL = PawaPayBaseUrl::SANDBOX;

    private const DEFAULT_SERVICE_FQCN = PawapayService::class;

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
}
