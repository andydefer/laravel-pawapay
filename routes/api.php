<?php

declare(strict_types=1);

use AndyDefer\Actions\Http\Requests\EmptyRequest;
use AndyDefer\LaravelPawapay\Http\Actions\CallbackAction;
use AndyDefer\LaravelPawapay\Http\Actions\CheckDepositStatusAction;
use AndyDefer\LaravelPawapay\Http\Actions\CreatePaymentPageAction;
use AndyDefer\LaravelPawapay\Http\Actions\InitiateDepositAction;
use AndyDefer\LaravelPawapay\Http\Actions\PredictProviderAction;
use AndyDefer\LaravelPawapay\Http\Actions\ResendDepositCallbackAction;
use AndyDefer\LaravelPawapay\Http\Requests\CheckDepositStatusRequest;
use AndyDefer\LaravelPawapay\Http\Requests\CreatePaymentPageRequest;
use AndyDefer\LaravelPawapay\Http\Requests\InitiateDepositRequest;
use AndyDefer\LaravelPawapay\Http\Requests\PredictProviderRequest;
use AndyDefer\LaravelPawapay\Http\Requests\ResendDepositCallbackRequest;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PawaPay API routes
|--------------------------------------------------------------------------
|
| Published to the host application so the developer can control the prefix,
| the middleware stack and the route name prefix.
|
| Load this file from the host application, for example:
|
|     require base_path('routes/laravel-pawapay.php');
|
*/

Route::prefix('pawapay')
    ->name('pawapay.')
    ->group(function (): void {
        Route::post('/initiate-deposit', action_route(
            InitiateDepositRequest::class,
            InitiateDepositAction::class,
        ))->name('initiate-deposit');

        Route::post('/check-deposit-status', action_route(
            CheckDepositStatusRequest::class,
            CheckDepositStatusAction::class,
        ))->name('check-deposit-status');

        Route::post('/resend-deposit-callback', action_route(
            ResendDepositCallbackRequest::class,
            ResendDepositCallbackAction::class,
        ))->name('resend-deposit-callback');

        Route::post('/create-payment-page', action_route(
            CreatePaymentPageRequest::class,
            CreatePaymentPageAction::class,
        ))->name('create-payment-page');

        Route::post('/predict-provider', action_route(
            PredictProviderRequest::class,
            PredictProviderAction::class,
        ))->name('predict-provider');

        Route::post('/callback', action_route(
            EmptyRequest::class,
            CallbackAction::class,
        ))->name('callback');
    });
