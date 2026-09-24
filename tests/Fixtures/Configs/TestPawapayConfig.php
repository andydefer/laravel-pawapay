<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Tests\Fixtures\Configs;

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

/**
 * Test fixture implementing {@see PawapayConfigInterface} with a fully
 * controlled configuration.
 *
 * Every value is exposed through a public property so that a test can
 * override it before binding the instance into the container.
 */
final class TestPawapayConfig implements PawapayConfigInterface
{
    public PawaPayBaseUrl $environment = PawaPayBaseUrl::SANDBOX;

    public string $apiToken = 'test-token';

    /** @var class-string */
    public string $serviceFqcn = PawapayService::class;

    /** @var class-string */
    public string $handleCallbackFqcn = HandlesCallback::class;

    public CurrencyCollection $currencies;

    public LanguageCollection $languages;

    public CountryCollection $countries;

    public ProviderCollection $providers;

    public PayerTypeCollection $payerTypes;

    public function __construct()
    {
        $this->currencies = CurrencyCollection::from([Currency::ZMW]);
        $this->languages = LanguageCollection::from([Language::EN]);
        $this->countries = CountryCollection::from([Country::ZMB]);
        $this->providers = ProviderCollection::from([Provider::MTN_MOMO_ZMB]);
        $this->payerTypes = PayerTypeCollection::from([PayerType::MMO]);
    }

    public function getEnvironment(): PawaPayBaseUrl
    {
        return $this->environment;
    }

    public function getApiToken(): string
    {
        return $this->apiToken;
    }

    public function getBaseUrl(): PawaPayBaseUrl
    {
        return $this->environment;
    }

    public function getServiceFqcn(): string
    {
        return $this->serviceFqcn;
    }

    public function getHandleCallbackFqcn(): string
    {
        return $this->handleCallbackFqcn;
    }

    public function getCurrencies(): CurrencyCollection
    {
        return $this->currencies;
    }

    public function getLanguages(): LanguageCollection
    {
        return $this->languages;
    }

    public function getCountries(): CountryCollection
    {
        return $this->countries;
    }

    public function getProviders(): ProviderCollection
    {
        return $this->providers;
    }

    public function getPayerTypes(): PayerTypeCollection
    {
        return $this->payerTypes;
    }
}
