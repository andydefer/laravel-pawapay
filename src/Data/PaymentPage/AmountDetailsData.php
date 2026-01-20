<?php

declare(strict_types=1);

namespace Pawapay\Data\PaymentPage;

use Pawapay\Enums\Currency;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;

class AmountDetailsData extends Data
{
    public function __construct(
        public string $amount,
        #[WithCast(EnumCast::class)]
        public Currency $currency
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            amount: $data['amount'],
            currency: Currency::from($data['currency'])
        );
    }
}
