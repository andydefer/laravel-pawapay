<?php

declare(strict_types=1);

namespace Pawapay\Data\PaymentPage;

use Pawapay\Data\Responses\FailureReasonData;
use Pawapay\Enums\TransactionStatus;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Casts\EnumCast;

final class PaymentPageErrorResponseData extends Data
{
    public function __construct(
        public string|Optional $depositId,
        #[WithCast(EnumCast::class)]
        public TransactionStatus|Optional $status,
        public FailureReasonData $failureReason,
    ) {}

    /**
     * Crée l'objet depuis un tableau API
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            depositId: $data['depositId'] ?? Optional::create(),
            status: isset($data['status'])
                ? TransactionStatus::from($data['status'])
                : Optional::create(),
            failureReason: FailureReasonData::fromArray($data['failureReason']),
        );
    }
}
