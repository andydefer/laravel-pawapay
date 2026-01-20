<?php

declare(strict_types=1);

namespace Pawapay\Data\PaymentPage;

use Spatie\LaravelData\Data;

class PaymentPageSuccessResponseData extends Data
{
    public function __construct(
        public string $redirectUrl
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            redirectUrl: $data['redirectUrl']
        );
    }
}
