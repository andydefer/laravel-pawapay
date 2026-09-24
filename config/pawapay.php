<?php

declare(strict_types=1);

use AndyDefer\LaravelPawapay\Callbacks\HandlesCallback;
use AndyDefer\PhpPawapay\Enums\Country;
use AndyDefer\PhpPawapay\Enums\Currency;
use AndyDefer\PhpPawapay\Enums\Language;
use AndyDefer\PhpPawapay\Enums\PawaPayBaseUrl;
use AndyDefer\PhpPawapay\Enums\PayerType;
use AndyDefer\PhpPawapay\Enums\Provider;
use AndyDefer\PhpPawapay\Services\PawapayService;

return [
    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Environnement PawaPay ciblé. Détermine à la fois l'URL de base utilisée
    | par le client HTTP et le token sélectionné dans la section `tokens`.
    |
    | Valeurs possibles : "sandbox" ou "production".
    |
    */
    'environment' => env('PAWAPAY_ENVIRONMENT', PawaPayBaseUrl::SANDBOX->value),

    /*
    |--------------------------------------------------------------------------
    | API Tokens
    |--------------------------------------------------------------------------
    |
    | Un token par environnement. Le token sélectionné dépend de la valeur
    | de la clé `environment` ci-dessus.
    |
    */
    'tokens' => [
        'sandbox' => env('PAWAPAY_SANDBOX_TOKEN', ''),
        'production' => env('PAWAPAY_PRODUCTION_TOKEN', ''),
    ],

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
    | Callback Handler
    |--------------------------------------------------------------------------
    |
    | FQCN du handler de callbacks lié à HandlesCallbacksInterface.
    |
    */
    'handle_callback_fqcn' => HandlesCallback::class,

    /*
    |--------------------------------------------------------------------------
    | Currencies
    |--------------------------------------------------------------------------
    */
    'currencies' => Currency::cases(),

    /*
    |--------------------------------------------------------------------------
    | Languages
    |--------------------------------------------------------------------------
    */
    'languages' => Language::cases(),

    /*
    |--------------------------------------------------------------------------
    | Countries
    |--------------------------------------------------------------------------
    */
    'countries' => Country::cases(),

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    */
    'providers' => Provider::cases(),

    /*
    |--------------------------------------------------------------------------
    | Payer Types
    |--------------------------------------------------------------------------
    */
    'payer_types' => PayerType::cases(),
];
