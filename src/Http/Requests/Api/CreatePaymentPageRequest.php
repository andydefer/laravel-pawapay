<?php

declare(strict_types=1);

namespace Pawapay\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Pawapay\Enums\Currency;
use Pawapay\Enums\Language;
use Pawapay\Enums\SupportedCountry;

class CreatePaymentPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'depositId' => ['required', 'uuid', 'max:255'],
            'returnUrl' => ['required', 'url', 'max:500'],
            'customerMessage' => [
                'nullable',
                'string',
                'min:4',
                'max:22'
            ],
            'amountDetails.amount' => ['required', 'string', 'regex:/^\d+(\.\d{1,2})?$/'],
            'amountDetails.currency' => ['required', Rule::enum(Currency::class)],
            'phoneNumber' => ['nullable', 'string', 'regex:/^(?:\+?\d{1,3}[- ]?)?\d{6,14}$/'],
            'language' => ['nullable', Rule::enum(Language::class)],
            'country' => ['nullable', Rule::enum(SupportedCountry::class)],
            'reason' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array', 'max:10'],
            'metadata.*' => ['required', 'array'],
            'metadata.*.*' => ['required', 'string'],
        ];
    }
}
