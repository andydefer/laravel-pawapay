<?php

declare(strict_types=1);

namespace Pawapay\Data\PaymentPage;

use Spatie\LaravelData\Data;

class PaymentPageSuccessResponseData extends Data
{
    public function __construct(
        public string $redirectUrl
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            redirectUrl: $data['redirectUrl']
        );
    }
}
