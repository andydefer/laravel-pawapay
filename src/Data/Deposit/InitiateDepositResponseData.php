<?php

declare(strict_types=1);

namespace Pawapay\Data\Deposit;

use Pawapay\Data\Responses\FailureReasonData;
use Pawapay\Enums\TransactionStatus;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Optional;

class InitiateDepositResponseData extends Data
{
    public function __construct(
        public string|Optional $depositId,

        #[WithCast(EnumCast::class)]
        public TransactionStatus $status,

        public string|Optional $created,

        public FailureReasonData|Optional $failureReason,
    ) {}

    /**
     * @param array<string, mixed> $response
     */
    public static function fromApiResponse(array $response): self
    {
        $depositId = $response['depositId'] ?? Optional::create();
        $created = $response['created'] ?? Optional::create();

        $failureReason = Optional::create();
        if (isset($response['failureReason']) && is_array($response['failureReason'])) {
            $failureReason = FailureReasonData::fromArray($response['failureReason']);
        }

        return new self(
            depositId: $depositId,
            status: TransactionStatus::from($response['status']),
            created: $created,
            failureReason: $failureReason,
        );
    }

    public function isAccepted(): bool
    {
        return $this->status === TransactionStatus::ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status === TransactionStatus::REJECTED;
    }

    public function isDuplicateIgnored(): bool
    {
        return $this->status === TransactionStatus::DUPLICATE_IGNORED;
    }

    public function isSuccessful(): bool
    {
        if ($this->isAccepted()) {
            return true;
        }
        return $this->isDuplicateIgnored();
    }
}
