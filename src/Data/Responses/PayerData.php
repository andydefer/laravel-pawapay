<?php


declare(strict_types=1);

namespace Pawapay\Data\Responses;

use Spatie\LaravelData\Data;


class PayerData extends Data
{
    public function __construct(
        public string $type,
        public AccountDetailsData $accountDetails,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            accountDetails: AccountDetailsData::fromArray($data['accountDetails']),
        );
    }
}
