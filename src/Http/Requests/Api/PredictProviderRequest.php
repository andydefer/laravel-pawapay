<?php

declare(strict_types=1);

namespace Pawapay\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PredictProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phoneNumber' => ['required', 'string', 'regex:/^(?:\+?\d{1,3}[- ]?)?\d{6,14}$/']
        ];
    }
}
