<?php

declare(strict_types=1);

namespace Pawapay\Enums;

/**
 * Represents all possible states of a payment transaction throughout its lifecycle.
 *
 * This enum covers transaction statuses from initiation through completion,
 * including intermediate states, final states, and search results.
 */
enum TransactionStatus: string
{
    // Initiation responses from API
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';
    case DUPLICATE_IGNORED = 'DUPLICATE_IGNORED';

        // Final states (callback/check status)
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';

        // Intermediate states
    case SUBMITTED = 'SUBMITTED';
    case ENQUEUED = 'ENQUEUED';
    case PROCESSING = 'PROCESSING';
    case IN_RECONCILIATION = 'IN_RECONCILIATION';

        // Search result states
    case FOUND = 'FOUND';
    case NOT_FOUND = 'NOT_FOUND';

    /**
     * Determine if the transaction has reached a terminal state.
     *
     * Terminal states indicate that no further processing will occur.
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::NOT_FOUND,
        ], true);
    }

    /**
     * Determine if the status represents an API initiation response.
     */
    public function isInitiationStatus(): bool
    {
        return in_array($this, [
            self::ACCEPTED,
            self::REJECTED,
            self::DUPLICATE_IGNORED,
        ], true);
    }

    /**
     * Determine if the transaction is in a non-terminal processing state.
     */
    public function isIntermediate(): bool
    {
        return in_array($this, [
            self::SUBMITTED,
            self::ENQUEUED,
            self::PROCESSING,
            self::IN_RECONCILIATION,
        ], true);
    }

    /**
     * Alias for isIntermediate() - indicates active processing.
     */
    public function isProcessing(): bool
    {
        return $this->isIntermediate();
    }

    /**
     * Determine if the transaction completed successfully.
     */
    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED || $this === self::FOUND;
    }

    /**
     * Determine if the transaction failed permanently.
     */
    public function isFailed(): bool
    {
        return $this === self::FAILED || $this === self::REJECTED;
    }

    /**
     * Determine if the transaction was found in search results.
     */
    public function isFound(): bool
    {
        return $this === self::FOUND;
    }

    /**
     * Determine if the transaction was not found in search results.
     */
    public function isNotFound(): bool
    {
        return $this === self::NOT_FOUND;
    }

    /**
     * Determine if the transaction was accepted for processing.
     */
    public function isAccepted(): bool
    {
        return $this === self::ACCEPTED;
    }

    /**
     * Determine if the transaction completed successfully.
     */
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Determine if the transaction was rejected.
     */
    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    /**
     * Determine if the request was a duplicate and ignored.
     */
    public function isDuplicateIgnored(): bool
    {
        return $this === self::DUPLICATE_IGNORED;
    }

    /**
     * Get a human-readable description of the transaction status.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ACCEPTED => 'Transaction accepted for processing',
            self::REJECTED => 'Transaction rejected',
            self::DUPLICATE_IGNORED => 'Duplicate request ignored',
            self::COMPLETED => 'Transaction successful',
            self::FAILED => 'Transaction failed',
            self::SUBMITTED => 'Submitted to payment provider',
            self::ENQUEUED => 'Queued for processing',
            self::PROCESSING => 'Processing in progress',
            self::IN_RECONCILIATION => 'Awaiting reconciliation',
            self::FOUND => 'Transaction found',
            self::NOT_FOUND => 'Transaction not found',
        };
    }

    /**
     * Determine if the status represents a business success case.
     *
     * Useful for determining if a transaction should be considered
     * successful from a business logic perspective.
     */
    public function isSuccess(): bool
    {
        return $this === self::COMPLETED ||
            $this === self::ACCEPTED ||
            $this === self::FOUND;
    }

    /**
     * Determine if the status requires manual intervention.
     *
     * Returns true for statuses that typically need human review
     * or manual handling.
     */
    public function requiresManualAction(): bool
    {
        return $this === self::FAILED ||
            $this === self::IN_RECONCILIATION;
    }
}
