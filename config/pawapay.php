<?php

declare(strict_types=1);

use AndyDefer\LaravelPawapay\Callbacks\HandlesCallback;
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
