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
            metadata: $data['metadata']  ?? null,
        );
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
