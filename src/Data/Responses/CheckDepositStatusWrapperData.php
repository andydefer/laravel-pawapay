<?php

declare(strict_types=1);

namespace Pawapay\Data\Responses;

use Pawapay\Enums\TransactionStatus;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Optional;

class CheckDepositStatusWrapperData extends Data
{
    public function __construct(
        #[WithCast(EnumCast::class)]
        public TransactionStatus $status,

        public CheckDepositStatusResponseData|Optional $data,
    ) {}

    /**
     * @param array<string, mixed> $response
     */
    public static function fromApiResponse(array $response): self
    {
        $status = TransactionStatus::from($response['status']);

        $data = Optional::create();
        if ($status === TransactionStatus::FOUND && isset($response['data'])) {
            $data = CheckDepositStatusResponseData::fromApiResponse($response);
        }

        return new self(
            status: $status,
            data: $data,
        );
    }

    public function isFound(): bool
    {
        return $this->status === TransactionStatus::FOUND;
    }

    public function isNotFound(): bool
    {
        return $this->status === TransactionStatus::NOT_FOUND;
    }
}
