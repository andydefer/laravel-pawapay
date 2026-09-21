<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Contracts;

use AndyDefer\PhpPawapay\Collections\CountryCollection;
use AndyDefer\PhpPawapay\Collections\CurrencyCollection;
use AndyDefer\PhpPawapay\Collections\LanguageCollection;
use AndyDefer\PhpPawapay\Contracts\PawapayInterface;
use AndyDefer\PhpPawapay\Enums\PawaPayBaseUrl;

/**
 * Contract for the Laravel PawaPay package configuration.
 *
 * Exposes the API credentials, the base URL, the concrete service implementation,
 * and the whitelists of allowed currencies, languages, and countries.
 *
 * Implementations are expected to be resolvable from the Laravel container,
 * either as singletons or as bound instances.
 */
interface PawapayConfigInterface
{
    /**
     * Return the API token used to authenticate against PawaPay.
     *
     * @return string The PawaPay API token.
     */
    public function getApiToken(): string;

    /**
     * Return the base URL of the PawaPay API to target.
     *
     * @return PawaPayBaseUrl Either sandbox or production.
     */
    public function getBaseUrl(): PawaPayBaseUrl;

    /**
     * Return the fully qualified class name of the service bound to
     * {@see PawapayInterface}.
     *
     * @return class-string The concrete service class.
     */
    public function getServiceFqcn(): string;

    /**
     * Return the currencies allowed by the package's HTTP requests.
     *
     * @return CurrencyCollection The authorized currencies.
     */
    public function getCurrencies(): CurrencyCollection;

    /**
     * Return the languages allowed by the package's HTTP requests.
     *
     * @return LanguageCollection The authorized languages.
     */
    public function getLanguages(): LanguageCollection;

    /**
     * Return the countries allowed by the package's HTTP requests.
     *
     * @return CountryCollection The authorized countries.
     */
    public function getCountries(): CountryCollection;
}
