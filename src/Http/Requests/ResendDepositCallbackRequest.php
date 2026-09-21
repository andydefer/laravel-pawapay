<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Http\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\PhpPawapay\Records\ResendDepositCallbackRecord;

/**
 * Validates the payload used to ask PawaPay to resend a deposit callback.
 */
final class ResendDepositCallbackRequest extends AbstractRequest
{
    /**
     * Return the validation rules for the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'deposit_id' => ['required', 'uuid'],
        ];
    }

    /**
     * Build the {@see ResendDepositCallbackRecord} from the validated payload.
     *
     * @return AbstractRecord The typed record passed to the action.
     */
    public function getRecord(): AbstractRecord
    {
        return ResendDepositCallbackRecord::from([
            'depositId' => $this->validated()['deposit_id'],
        ]);
    }
}
