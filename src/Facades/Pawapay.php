<?php

declare(strict_types=1);

namespace Pawapay\Facades;

use Pawapay\Services\PawapayClient;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PawapayClient payIn(array $payload)
 * @method static PawapayClient payOut(array $payload)
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
