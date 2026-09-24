<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Contracts;

use AndyDefer\PhpPawapay\Collections\CountryCollection;
use AndyDefer\PhpPawapay\Collections\CurrencyCollection;
use AndyDefer\PhpPawapay\Collections\LanguageCollection;
use AndyDefer\PhpPawapay\Collections\PayerTypeCollection;
use AndyDefer\PhpPawapay\Collections\ProviderCollection;
use AndyDefer\PhpPawapay\Contracts\Callbacks\HandlesCallbacksInterface;
use AndyDefer\PhpPawapay\Contracts\PawapayInterface;
use AndyDefer\PhpPawapay\Enums\PawaPayBaseUrl;

/**
 * Contract for the Laravel PawaPay package configuration.
 *
 * Exposes the API credentials, the base URL, the concrete service implementation,
 * the callback handler implementation, and the whitelists of allowed currencies,
 * languages, countries, providers and payer types used by the HTTP requests.
 */
interface PawapayConfigInterface
{
    /**
     * Return the API token used to authenticate against PawaPay.
     */
    public function getApiToken(): string;

    /**
     * Return the base URL of the PawaPay API to target.
     */
    public function getBaseUrl(): PawaPayBaseUrl;

    /**
     * Return the fully qualified class name of the service bound to
     * {@see PawapayInterface}.
     *
     * @return class-string<PawapayInterface>
     */
    public function getServiceFqcn(): string;

    /**
     * Return the fully qualified class name of the callback handler bound
     * to {@see HandlesCallbacksInterface}.
     *
     * @return class-string<HandlesCallbacksInterface>
     */
    public function getHandleCallbackFqcn(): string;

    /**
     * Return the currencies allowed by the package's HTTP requests.
     */
    public function getCurrencies(): CurrencyCollection;

    /**
     * Return the languages allowed by the package's HTTP requests.
     */
    public function getLanguages(): LanguageCollection;

    /**
     * Return the countries allowed by the package's HTTP requests.
     */
    public function getCountries(): CountryCollection;

    /**
     * Return the Mobile Money providers allowed by the package's HTTP requests.
     */
    public function getProviders(): ProviderCollection;

    /**
     * Return the payer types allowed by the package's HTTP requests.
     */
    public function getPayerTypes(): PayerTypeCollection;
}
