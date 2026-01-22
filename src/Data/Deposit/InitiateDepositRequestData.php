<?php

declare(strict_types=1);

namespace Pawapay\Data\Deposit;

use Pawapay\Data\Responses\FailureReasonData;
use Pawapay\Data\Responses\PayerData;
use Pawapay\Enums\Currency;
use Pawapay\Enums\TransactionStatus;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Optional;

/**
 * Data Transfer Object for initiating a deposit request.
 */
class InitiateDepositRequestData extends Data
{
    /**
     * @param string $depositId Unique identifier for the deposit
     * @param PayerData $payer Information about the payer
     * @param string $amount Deposit amount
     * @param Currency $currency Currency of the deposit
     * @param string|Optional $preAuthorisationCode Optional pre-authorisation code
     * @param string|Optional $clientReferenceId Optional client reference identifier
     * @param string|Optional $customerMessage Optional message for the customer
     * @param array<mixed>|Optional $metadata Optional metadata associated with the deposit
     */
    public function __construct(
        public string $depositId,
        public PayerData $payer,
        public string $amount,
        #[WithCast(EnumCast::class)]
        public Currency $currency,
        public string|Optional $preAuthorisationCode,
        public string|Optional $clientReferenceId,
        public string|Optional $customerMessage,
        public array|Optional $metadata,
    ) {}

    /**
     * Create an instance from an array of data.
     *
     * @param array<string, mixed> $data
     * @return self
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
