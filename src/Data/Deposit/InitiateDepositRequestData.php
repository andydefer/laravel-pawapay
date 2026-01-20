<?php

declare(strict_types=1);

namespace Pawapay\Data\Deposit;

use Pawapay\Enums\Currency;
use Pawapay\Enums\SupportedProvider;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Optional;

class InitiateDepositRequestData extends Data
{
    public function __construct(
        public string $depositId,

        public PayerData $payer,

        public string $amount,

        #[WithCast(EnumCast::class)]
        public Currency $currency,

        public string|Optional $preAuthorisationCode,

        public string|Optional $clientReferenceId,

        public string|Optional $customerMessage,

        /** @var array<mixed>|Optional */
        public array|Optional $metadata,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $payer = PayerData::fromArray($data['payer']);
        $metadata = $data['metadata'] ?? Optional::create();

        return new self(
            depositId: $data['depositId'],
            payer: $payer,
            amount: $data['amount'],
            currency: Currency::from($data['currency']),
            preAuthorisationCode: $data['preAuthorisationCode'] ?? Optional::create(),
            clientReferenceId: $data['clientReferenceId'] ?? Optional::create(),
            customerMessage: $data['customerMessage'] ?? Optional::create(),
            metadata: $metadata,
        );
    }
}

class PayerData extends Data
{
    public function __construct(
        public string $type = 'MMO',
        public ?AccountDetailsData $accountDetails = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'] ?? 'MMO',
            accountDetails: AccountDetailsData::fromArray($data['accountDetails']),
        );
    }
}

class AccountDetailsData extends Data
{
    public function __construct(
        public string $phoneNumber,

        #[WithCast(EnumCast::class)]
        public SupportedProvider $provider,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (isset($data['phoneNumber'])) {
            // Supprime le + au début si présent
            $data['phoneNumber'] = ltrim($data['phoneNumber'], '+');
        }

        return new self(
            phoneNumber: $data['phoneNumber'],
            provider: SupportedProvider::from($data['provider']),
        );
    }
}
