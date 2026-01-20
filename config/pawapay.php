<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Pawapay API Configuration
    |--------------------------------------------------------------------------
    */

    'api' => [
        'sandbox_url' => env('PAWAPAY_SANDBOX_URL', 'https://api.sandbox.pawapay.io/v2'),
        'production_url' => env('PAWAPAY_PRODUCTION_URL', 'https://api.pawapay.io/v2'),
        'token' => env('PAWAPAY_API_TOKEN'),
        'timeout' => env('PAWAPAY_TIMEOUT', 30),
        'retry_times' => env('PAWAPAY_RETRY_TIMES', 3),
        'retry_sleep' => env('PAWAPAY_RETRY_SLEEP', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Settings
    |--------------------------------------------------------------------------
    */

    'environment' => env('PAWAPAY_ENVIRONMENT', 'sandbox'),

    'defaults' => [
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
    ],
];
