<?php

declare(strict_types=1);

namespace Pawapay\Data\PaymentPage;

use InvalidArgumentException;
use Pawapay\Enums\Language;
use Pawapay\Enums\SupportedCountry;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

final class PaymentPageRequestData extends Data
{
    public function __construct(
        public string $depositId,
        public string $returnUrl,
        public string $customerMessage,
        public AmountDetailsData $amountDetails,
        public string $phoneNumber,
        #[WithCast(EnumCast::class)]
        public Language $language,
        #[WithCast(EnumCast::class)]
        public SupportedCountry $country,
        public string $reason,
        /** @var array<int, array<string, string>> */
        public array $metadata
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        self::validateRequiredFields($data);

        $amountDetails = AmountDetailsData::fromArray($data['amountDetails']);

        $metadata = self::validateMetadata($data['metadata']);

        return new self(
            depositId: $data['depositId'],
            returnUrl: $data['returnUrl'],
            customerMessage: $data['customerMessage'],
            amountDetails: $amountDetails,
            phoneNumber: $data['phoneNumber'],
            language: Language::from($data['language']),
            country: SupportedCountry::from($data['country']),
            reason: $data['reason'],
            metadata: $metadata
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function validateRequiredFields(array $data): void
    {
        $requiredFields = [
            'depositId',
            'returnUrl',
            'customerMessage',
            'amountDetails',
            'phoneNumber',
            'language',
            'country',
            'reason',
            'metadata'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException(
                    sprintf('Le champ "%s" est requis.', $field)
                );
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $metadata
     * @return array<int, array<string, mixed>>
     */
    private static function validateMetadata(array $metadata): array
    {
        if (!array_is_list($metadata)) {
            throw new InvalidArgumentException('Metadata doit être une liste de tableaux associatifs.');
        }

        foreach ($metadata as $index => $item) {
            if (!is_array($item) || array_is_list($item)) {
                throw new InvalidArgumentException(
                    sprintf('L\'élément metadata à l\'index %d doit être un tableau associatif.', $index)
                );
            }

            foreach ($item as $key => $value) {
                if (!is_string($key)) {
                    throw new InvalidArgumentException(
                        sprintf('L\'élément metadata à l\'index %d doit avoir des clés de type string.', $index)
                    );
                }

                if (!is_scalar($value)) {
                    throw new InvalidArgumentException(
                        sprintf('L\'élément metadata à l\'index %d doit avoir des valeurs scalaires.', $index)
                    );
                }
            }
        }

        return $metadata;
    }
}
