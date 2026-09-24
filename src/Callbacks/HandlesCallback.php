<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Callbacks;

use AndyDefer\PhpPawapay\Contracts\Callbacks\HandlesCallbacksInterface;
use AndyDefer\PhpPawapay\Structures\Callbacks\CheckoutCallbackStruct;
use AndyDefer\PhpPawapay\Structures\Callbacks\DepositCallbackStruct;
use AndyDefer\PhpPawapay\Structures\Callbacks\PayoutCallbackStruct;
use AndyDefer\PhpPawapay\Structures\Callbacks\RefundCallbackStruct;

/**
 * Default callback handler.
 *
 * Provides an empty implementation of every callback method. Applications
 * are expected to override this class (or bind their own implementation of
 * {@see HandlesCallbacksInterface}) and populate the relevant methods.
 */
class HandlesCallback implements HandlesCallbacksInterface
{
    public function handleDeposit(DepositCallbackStruct $struct): void {}

    public function handlePayout(PayoutCallbackStruct $struct): void {}

    public function handleRefund(RefundCallbackStruct $struct): void {}

    public function handleCheckout(CheckoutCallbackStruct $struct): void {}
}
