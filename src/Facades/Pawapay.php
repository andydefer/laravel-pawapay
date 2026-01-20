<?php

namespace Pawapay\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Pawapay\Services\PawapayClient payIn(array $payload)
 * @method static \Pawapay\Services\PawapayClient payOut(array $payload)
 * @method static mixed verify(string $transactionId)
 *
 * @see \Pawapay\Services\PawapayClient
 */
class Pawapay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'pawapay';
    }
}
