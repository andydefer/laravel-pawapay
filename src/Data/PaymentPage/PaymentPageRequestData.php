<?php

declare(strict_types=1);

namespace Pawapay\Data\PaymentPage;

use Pawapay\Enums\Language;
use Pawapay\Enums\SupportedCountry;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class PaymentPageRequestData extends Data
{
    public function __construct(
        public string $depositId,
        public string $returnUrl,
        public string|Optional $customerMessage,
        public AmountDetailsData|Optional $amountDetails,
        public string|Optional $phoneNumber,
        #[WithCast(EnumCast::class)]
        public Language|Optional $language,
        #[WithCast(EnumCast::class)]
        public SupportedCountry|Optional $country,
        public string|Optional $reason,
        /** @var array<mixed>|Optional Metadata libre */
        public array|Optional $metadata
    ) {}

    public static function fromArray(array $data): static
    {
        $amountDetails = isset($data['amountDetails'])
            ? AmountDetailsData::fromArray($data['amountDetails'])
            : Optional::create();

        $metadata = $data['metadata'] ?? Optional::create();

        return new static(
            depositId: $data['depositId'],
            returnUrl: $data['returnUrl'],
            customerMessage: $data['customerMessage'] ?? Optional::create(),
            amountDetails: $amountDetails,
            phoneNumber: $data['phoneNumber'] ?? Optional::create(),
            language: isset($data['language'])
                ? Language::from($data['language'])
                : Optional::create(),
            country: isset($data['country'])
                ? SupportedCountry::from($data['country'])
                : Optional::create(),
            reason: $data['reason'] ?? Optional::create(),
            metadata: $metadata
        );
    }
}
