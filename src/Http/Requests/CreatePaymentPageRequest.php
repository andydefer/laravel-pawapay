<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Http\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Utils\StrictAssociative;
use AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface;
use AndyDefer\PhpPawapay\Records\CreatePaymentPageRecord;
use Illuminate\Validation\Rule;

/**
 * Validates the payload used to create a PawaPay-hosted payment page.
 *
 * Allowed currencies, languages, and countries are sourced from the package
 * configuration so the host application can restrict the accepted values.
 *
 * Fields not declared in the validation rules are collected into the `data`
 * property of the record.
 */
final class CreatePaymentPageRequest extends AbstractRequest
{
    /**
     * Fields mapped to first-class record properties and excluded from the
     * generic `data` bag.
     *
     * @var array<int, string>
     */
    private const RESERVED_FIELDS = [
        'deposit_id',
        'return_url',
        'amount',
        'currency',
        'phone_number',
        'language',
        'country',
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
            'return_url' => ['required', 'url'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', Rule::in($this->config->getCurrencies()->toValues())],
            'phone_number' => ['required', 'string', 'min:9', 'max:15'],
            'language' => ['required', Rule::in($this->config->getLanguages()->toValues())],
            'country' => ['required', Rule::in($this->config->getCountries()->toValues())],
            'customer_message' => ['nullable', 'string', 'min:4', 'max:22'],
            'metadata' => ['nullable', 'array', 'max:10'],
        ];
    }

    /**
     * Build the {@see CreatePaymentPageRecord} from the validated payload.
     *
     * @return AbstractRecord The typed record passed to the action.
     */
    public function getRecord(): AbstractRecord
    {
        $data = $this->validated();

        return CreatePaymentPageRecord::from([
            'depositId' => $data['deposit_id'],
            'returnUrl' => $data['return_url'],
            'amountDetails' => [
                'amount' => $data['amount'],
                'currency' => $data['currency'],
            ],
            'phoneNumber' => $data['phone_number'],
            'language' => $data['language'],
            'country' => $data['country'],
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
