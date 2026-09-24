<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Http\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\PhpPawapay\Records\PredictProviderRecord;
use AndyDefer\PhpPawapay\ValueObjects\PhoneNumberVO;

/**
 * Validates the incoming phone number used to predict a PawaPay provider.
 *
 * The record carries only the phone number; the service reads everything
 * else from the underlying PawaPay response.
 */
final class PredictProviderRequest extends AbstractRequest
{
    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            'phone_number' => [
                'required',
                'string',
                'regex:/^[1-9]\d{8,14}$/',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function messages(): array
    {
        return [
            'phone_number.required' => 'The phone number is required.',
            'phone_number.regex' => 'The phone number must be in E.164 format without the + sign.',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRecord(): AbstractRecord
    {
        $data = $this->validated();

        return PredictProviderRecord::from([
            'phoneNumber' => PhoneNumberVO::from($data['phone_number']),
        ]);
    }
}
