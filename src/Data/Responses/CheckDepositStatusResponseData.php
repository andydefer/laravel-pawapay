<?php

declare(strict_types=1);

namespace Pawapay\Data\Responses;

use InvalidArgumentException;
use Pawapay\Enums\TransactionStatus;
use Pawapay\Enums\Currency;
use Pawapay\Enums\SupportedCountry;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;

class CheckDepositStatusResponseData extends Data
{
    public function __construct(
        public string $depositId,

        #[WithCast(EnumCast::class)]
        public TransactionStatus $status,

        public string $amount,

        #[WithCast(EnumCast::class)]
        public Currency $currency,

        #[WithCast(EnumCast::class)]
        public SupportedCountry $country,

        public PayerData $payer,

        public ?string $customerMessage = null,

        public ?string $clientReferenceId = null,

        public ?string $providerTransactionId = null,

        public ?string $created = null,

        public ?FailureReasonData $failureReason = null,

        public ?array $metadata = null,
    ) {}

    /**
     * @param array<string, mixed> $response
     */
    public static function fromApiResponse(array $response): self
    {
        $data = $response['data'] ?? $response;

        $payer = PayerData::fromArray($data['payer']);

        $failureReason = null;
        if (isset($data['failureReason']) && is_array($data['failureReason'])) {
            $failureReason = FailureReasonData::fromArray($data['failureReason']);
        }

        $metadata = self::validateMetadata($data['metadata'] ?? null);

        return new self(
            depositId: $data['depositId'],
            status: TransactionStatus::from($data['status']),
            amount: $data['amount'],
            currency: Currency::from($data['currency']),
            country: SupportedCountry::from($data['country']),
            payer: $payer,
            customerMessage: $data['customerMessage'] ?? null,
            clientReferenceId: $data['clientReferenceId'] ?? null,
            providerTransactionId: $data['providerTransactionId'] ?? null,
            created: $data['created'] ?? null,
            failureReason: $failureReason,
            metadata: $metadata,
        );
    }

    /**
     * @param array<int, array<string, mixed>>|null $metadata
     * @return array<int, array<string, mixed>>|null
     */
    private static function validateMetadata(?array $metadata): ?array
    {
        if ($metadata === null) {
            return null;
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

    public function isFinalStatus(): bool
    {
        return in_array($this->status, [
            TransactionStatus::COMPLETED,
            TransactionStatus::FAILED,
        ]);
    }

    public function isProcessing(): bool
    {
        return in_array($this->status, [
            TransactionStatus::ACCEPTED,
            TransactionStatus::PROCESSING,
            TransactionStatus::SUBMITTED,
            TransactionStatus::ENQUEUED,
            TransactionStatus::IN_RECONCILIATION,
        ]);
    }
}
