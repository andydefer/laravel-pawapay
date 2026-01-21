<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Pawapay\Http\Controllers\Api\PawapayController;

Route::prefix('api/pawapay')
    ->name('pawapay.')
    ->group(function () {
        // Predict provider from phone number
        Route::post('predict-provider', [PawapayController::class, 'predictProvider'])
            ->name('predict-provider');

        // Create payment page
        Route::post('payment-page', [PawapayController::class, 'createPaymentPage'])
            ->name('payment-page');

        // Initiate direct deposit
        Route::post('deposits', [PawapayController::class, 'initiateDeposit'])
            ->name('deposits.initiate');

        // Check deposit status
        Route::get('deposits/{depositId}', [PawapayController::class, 'checkDepositStatus'])
            ->where('depositId', '[a-zA-Z0-9\-_]+')
            ->name('deposits.status');
    });
