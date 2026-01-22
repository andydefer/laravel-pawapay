<?php

declare(strict_types=1);

namespace Pawapay\Data\Deposit;

use Pawapay\Data\Responses\FailureReasonData;
use Pawapay\Enums\TransactionStatus;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Optional;

/**
 * Data Transfer Object for deposit initiation response.
 */
class InitiateDepositResponseData extends Data
{
    /**
     * @param string|Optional $depositId Unique identifier for the deposit
     * @param TransactionStatus $status Status of the deposit transaction
     * @param string|Optional $created Timestamp when the deposit was created
     * @param FailureReasonData|Optional $failureReason Reason for transaction failure, if applicable
     */
    public function __construct(
        public string|Optional $depositId,
        #[WithCast(EnumCast::class)]
        public TransactionStatus $status,
        public string|Optional $created,
        public FailureReasonData|Optional $failureReason,
    ) {}

    /**
     * Create an instance from API response data.
     *
     * @param array<string, mixed> $response
     * @return self
     */
    public static function fromApiResponse(array $response): self
    {
        $depositId = $response['depositId'] ?? Optional::create();
        $created = $response['created'] ?? Optional::create();

        $failureReason = Optional::create();
        if (isset($response['failureReason'])) {
            $failureReason = FailureReasonData::fromArray($response['failureReason']);
        }

        return new self(
            depositId: $depositId,
            status: TransactionStatus::from($response['status']),
            created: $created,
            failureReason: $failureReason,
        );
    }

    /**
     * Check if the deposit was accepted.
     *
     * @return bool
     */
    public function isAccepted(): bool
    {
        return $this->status === TransactionStatus::ACCEPTED;
    }

    /**
     * Check if the deposit was rejected.
     *
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->status === TransactionStatus::REJECTED;
    }

    /**
     * Check if the deposit was ignored as a duplicate.
     *
     * @return bool
     */
    public function isDuplicateIgnored(): bool
    {
        return $this->status === TransactionStatus::DUPLICATE_IGNORED;
    }

    /**
     * Check if the deposit transaction was successful.
     *
     * A deposit is considered successful if it was either accepted
     * or ignored as a duplicate (which means a previous identical request succeeded).
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->isAccepted() || $this->isDuplicateIgnored();
    }
}
