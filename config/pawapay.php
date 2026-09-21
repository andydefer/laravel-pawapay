<?php

declare(strict_types=1);

use AndyDefer\PhpPawapay\Enums\Country;
use AndyDefer\PhpPawapay\Enums\Currency;
use AndyDefer\PhpPawapay\Enums\Language;
use AndyDefer\PhpPawapay\Enums\PayerType;
use AndyDefer\PhpPawapay\Enums\Provider;
use AndyDefer\PhpPawapay\Services\PawapayService;

return [
    /*
    |--------------------------------------------------------------------------
    | API Token
    |--------------------------------------------------------------------------
    |
    | Token d'authentification fourni par PawaPay.
    |
    */
    'api_token' => env('PAWAPAY_API_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | Environnement ciblé : "sandbox" ou "production".
    |
    */
    'base_url' => env('PAWAPAY_BASE_URL', 'https://api.sandbox.pawapay.io/'),

    /*
    |--------------------------------------------------------------------------
    | Service
    |--------------------------------------------------------------------------
    |
    | FQCN du service PawaPay lié à PawapayInterface.
    |
    */
    'service_fqcn' => PawapayService::class,

    /*
    |--------------------------------------------------------------------------
    | Currencies
    |--------------------------------------------------------------------------
    |
    | Devises autorisées. Par défaut : toutes les cases de l'enum Currency.
    | Surcharger avec un tableau de valeurs (ex: ['USD', 'CDF']).
    |
    */
    'currencies' => Currency::cases(),

    /*
    |--------------------------------------------------------------------------
    | Languages
    |--------------------------------------------------------------------------
    |
    | Langues autorisées. Par défaut : toutes les cases de l'enum Language.
    |
    */
    'languages' => Language::cases(),

    /*
    |--------------------------------------------------------------------------
    | Countries
    |--------------------------------------------------------------------------
    |
    | Pays autorisés. Par défaut : toutes les cases de l'enum Country.
    |
    */
    'countries' => Country::cases(),

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | Fournisseurs Mobile Money autorisés. Par défaut : toutes les cases de
    | l'enum Provider.
    |
    */
    'providers' => Provider::cases(),

    /*
    |--------------------------------------------------------------------------
    | Payer Types
    |--------------------------------------------------------------------------
    |
    | Types de payeur autorisés. Par défaut : toutes les cases de l'enum
    | PayerType.
    |
    */
    'payer_types' => PayerType::cases(),
];
