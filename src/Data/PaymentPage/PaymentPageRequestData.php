<?php

declare(strict_types=1);

namespace Pawapay\Data\PaymentPage;

use InvalidArgumentException;
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
        /** @var array<int, array<string, mixed>>|Optional */
        public array|Optional $metadata
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $amountDetails = isset($data['amountDetails'])
            ? AmountDetailsData::fromArray($data['amountDetails'])
            : Optional::create();

        $metadata = self::validateMetadata($data['metadata'] ?? Optional::create());

        return new self(
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

    /**
     * @param array<int, array<string, mixed>>|Optional $metadata
     * @return array<int, array<string, mixed>>|Optional
     */
    private static function validateMetadata(array|Optional $metadata): array|Optional
    {
        if ($metadata instanceof Optional) {
            return $metadata;
        }

        if (! array_is_list($metadata)) {
            throw new InvalidArgumentException('Metadata must be a list of associative arrays.');
        }

        foreach ($metadata as $index => $item) {
            if (! is_array($item) || array_is_list($item)) {
                throw new InvalidArgumentException(
                    sprintf('Metadata item at index %d must be an associative array.', $index)
                );
            }

            foreach ($item as $key => $value) {
                if (! is_string($key)) {
                    throw new InvalidArgumentException(
                        sprintf('Metadata item at index %d must have string keys.', $index)
                    );
                }

                if (! is_scalar($value)) {
                    throw new InvalidArgumentException(
                        sprintf('Metadata item at index %d must have scalar values.', $index)
                    );
                }
            }
        }

        return $metadata;
    }
}
