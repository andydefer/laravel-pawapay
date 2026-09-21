<?php

declare(strict_types=1);

use AndyDefer\LaravelPawapay\Http\Actions\CheckDepositStatusAction;
use AndyDefer\LaravelPawapay\Http\Actions\CreatePaymentPageAction;
use AndyDefer\LaravelPawapay\Http\Actions\InitiateDepositAction;
use AndyDefer\LaravelPawapay\Http\Actions\ResendDepositCallbackAction;
use AndyDefer\LaravelPawapay\Http\Requests\CheckDepositStatusRequest;
use AndyDefer\LaravelPawapay\Http\Requests\CreatePaymentPageRequest;
use AndyDefer\LaravelPawapay\Http\Requests\InitiateDepositRequest;
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
        Route::post('/deposits', action_route(
            InitiateDepositRequest::class,
            InitiateDepositAction::class,
        ))->name('deposits.initiate');

        Route::post('/deposits/status', action_route(
            CheckDepositStatusRequest::class,
            CheckDepositStatusAction::class,
        ))->name('deposits.status');

        Route::post('/deposits/resend-callback', action_route(
            ResendDepositCallbackRequest::class,
            ResendDepositCallbackAction::class,
        ))->name('deposits.resend-callback');

        Route::post('/payment-page', action_route(
            CreatePaymentPageRequest::class,
            CreatePaymentPageAction::class,
        ))->name('payment-page');
    });
