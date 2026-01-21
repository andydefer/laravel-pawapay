<?php

declare(strict_types=1);

namespace Pawapay\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CheckDepositStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'depositId' => ['required', 'uuid', 'max:255']
        ];
    }

    public function validationData()
    {
        return [
            'depositId' => $this->route('depositId')
        ];
    }
}
