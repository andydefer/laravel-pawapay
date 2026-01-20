<?php

declare(strict_types=1);

namespace Pawapay\Data\Responses;

use Pawapay\Enums\SupportedCountry;
use Pawapay\Enums\SupportedProvider;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Optional;

class PredictProviderResponseData extends Data
{
    public function __construct(
        #[WithCast(EnumCast::class)]
        public SupportedCountry|Optional $country,

        #[WithCast(EnumCast::class)]
        public SupportedProvider|Optional $provider,

        public string|Optional $phoneNumber,

        public FailureReasonData|Optional $failureReason,

        public string|Optional $depositId,

        public string|Optional $status,
    ) {}

    /**
     * @param array<string, mixed> $response
     */
    public static function fromApiResponse(array $response): self
    {
        // Check for success response
        if (isset($response['country']) && isset($response['provider'])) {
            return new self(
                country: SupportedCountry::from($response['country']),
                provider: SupportedProvider::from($response['provider']),
                phoneNumber: $response['phoneNumber'],
                failureReason: Optional::create(),
                depositId: Optional::create(),
                status: Optional::create(),
            );
        }

        // Check for failure response
        $failureReason = isset($response['failureReason'])
            ? FailureReasonData::fromArray($response['failureReason'])
            : Optional::create();

        return new self(
            country: Optional::create(),
            provider: Optional::create(),
            phoneNumber: Optional::create(),
            failureReason: $failureReason,
            depositId: $response['depositId'] ?? Optional::create(),
            status: $response['status'] ?? Optional::create(),
        );
    }

    public function isSuccess(): bool
    {
        return $this->country instanceof SupportedCountry
            && $this->provider instanceof SupportedProvider;
    }

    public function isFailure(): bool
    {
        return $this->failureReason instanceof FailureReasonData;
    }
}
