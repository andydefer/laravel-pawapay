<?php

namespace PawaPay\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \PawaPay\Services\PawaPayClient payIn(array $payload)
 * @method static \PawaPay\Services\PawaPayClient payOut(array $payload)
 * @method static mixed verify(string $transactionId)
 *
 * @see \PawaPay\Services\PawaPayClient
 */
class PawaPay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'pawapay';
    }
}
