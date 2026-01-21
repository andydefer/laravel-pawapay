<?php

declare(strict_types=1);

namespace Pawapay\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Pawapay\Enums\Currency;
use Pawapay\Enums\SupportedProvider;

class InitiateDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'depositId' => ['required', 'uuid', 'max:255'],
            'payer.type' => ['required', 'string', 'in:MMO'],
            'payer.accountDetails.phoneNumber' => ['required', 'string', 'regex:/^(?:\+?\d{1,3}[- ]?)?\d{6,14}$/'],
            'payer.accountDetails.provider' => ['required', Rule::enum(SupportedProvider::class)],
            'amount' => ['required', 'string', 'regex:/^\d+(\.\d{1,2})?$/'],
            'currency' => ['required', Rule::enum(Currency::class)],
            'preAuthorisationCode' => ['nullable', 'string', 'max:255'],
            'clientReferenceId' => ['nullable', 'string', 'max:255'],
            'customerMessage' => [
                'nullable',
                'string',
                'min:4',
                'max:22'
            ],
            'metadata' => ['nullable', 'array', 'max:10'],
            'metadata.*' => ['required', 'array'],
            'metadata.*.*' => ['required', 'string'],
        ];
    }
}
