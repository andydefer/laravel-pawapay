<?php

declare(strict_types=1);

namespace Pawapay\Data\Responses;

use Pawapay\Enums\SupportedCountry;
use Pawapay\Enums\SupportedProvider;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;

class PredictProviderSuccessResponse extends Data
{
    public function __construct(
        #[WithCast(EnumCast::class)]
        public SupportedCountry $country,

        #[WithCast(EnumCast::class)]
        public SupportedProvider $provider,

        public string $phoneNumber,
    ) {}

    public static function fromArray($data)
    {
        return new self(
            country: SupportedCountry::from($data['country']),
            provider: SupportedProvider::from($data['provider']),
            phoneNumber: $data['phoneNumber'],
        );
    }
}
