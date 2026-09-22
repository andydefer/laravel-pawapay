<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Http\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Utils\StrictAssociative;
use AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface;
use AndyDefer\PhpPawapay\Records\InitiateDepositRecord;
use Illuminate\Validation\Rule;

/**
 * Validates the payload used to initiate a Mobile Money deposit through PawaPay.
 *
 * Allowed currencies, providers and payer types are sourced from the package
 * configuration so the host application can restrict the accepted values.
 *
 * Fields not declared in the validation rules are collected into the `data`
 * property of the record, so the host application can attach contextual
 * information without extending the record.
 */
final class InitiateDepositRequest extends AbstractRequest
{
    /**
     * Fields that are mapped to first-class record properties and must not
     * leak into the generic `data` bag.
     *
     * @var array<int, string>
     */
    private const RESERVED_FIELDS = [
        'deposit_id',
        'phone_number',
        'provider',
        'amount',
        'currency',
        'payer_type',
        'pre_authorisation_code',
        'client_reference_id',
        'customer_message',
        'metadata',
    ];

    public function __construct(
        private readonly PawapayConfigInterface $config,
    ) {
        parent::__construct();
    }

    /**
     * Return the validation rules for the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'deposit_id' => ['required', 'uuid'],
            'phone_number' => ['required', 'string', 'min:9', 'max:15'],
            'provider' => ['required', Rule::in($this->config->getProviders()->toValues())],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', Rule::in($this->config->getCurrencies()->toValues())],
            'payer_type' => ['required', Rule::in($this->config->getPayerTypes()->toValues())],
            'pre_authorisation_code' => ['nullable', 'string'],
            'client_reference_id' => ['nullable', 'string', 'min:4', 'max:64'],
            'customer_message' => ['nullable', 'string', 'min:4', 'max:22'],
            'metadata' => ['nullable', 'array', 'max:10'],
        ];
    }

    /**
     * Build the {@see InitiateDepositRecord} from the validated payload.
     *
     * Every key present in the request but not listed in {@see self::RESERVED_FIELDS}
     * is collected into the record's `data` property.
     *
     * @return AbstractRecord The typed record passed to the action.
     */
    public function getRecord(): AbstractRecord
    {
        $data = $this->validated();

        return InitiateDepositRecord::from([
            'depositId' => $data['deposit_id'],
            'payer' => [
                'type' => $data['payer_type'],
                'accountDetails' => [
                    'phoneNumber' => $data['phone_number'],
                    'provider' => $data['provider'],
                ],
            ],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'preAuthorisationCode' => $data['pre_authorisation_code'] ?? null,
            'clientReferenceId' => $data['client_reference_id'] ?? null,
            'customerMessage' => $data['customer_message'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'data' => $this->buildDataBag(),
        ]);
    }

    /**
     * Collect every request field not reserved by the record into a strict
     * associative bag.
     *
     * @return StrictAssociative|null The extra fields, or null when none are present.
     */
    private function buildDataBag(): ?StrictAssociative
    {
        $extra = array_diff_key(
            $this->all(),
            array_flip(self::RESERVED_FIELDS),
        );

        if ($extra === []) {
            return null;
        }

        return StrictAssociative::from($extra);
    }
}
