<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Tests\Fixtures\Services;

use AndyDefer\PhpPawapay\Contracts\PawapayClientInterface;
use AndyDefer\PhpPawapay\Datas\CheckDepositStatusData;
use AndyDefer\PhpPawapay\Datas\CreatePaymentPageData;
use AndyDefer\PhpPawapay\Datas\ErrorResponseData;
use AndyDefer\PhpPawapay\Datas\InitiateDepositData;
use AndyDefer\PhpPawapay\Datas\PredictProviderData;
use AndyDefer\PhpPawapay\Datas\ResendDepositCallbackData;
use AndyDefer\PhpPawapay\Records\CheckDepositStatusRecord;
use AndyDefer\PhpPawapay\Records\CreatePaymentPageRecord;
use AndyDefer\PhpPawapay\Records\InitiateDepositRecord;
use AndyDefer\PhpPawapay\Records\PredictProviderRecord;
use AndyDefer\PhpPawapay\Records\ResendDepositCallbackRecord;
use AndyDefer\PhpPawapay\Services\PawapayService;

/**
 * Test fixture that lets tests force a specific hook to return an error
 * or to mutate the incoming Record.
 *
 * Each public property holds an optional value:
 *  - `before*` hooks hold an `ErrorResponseData` (short-circuit) or a `Record` (mutation).
 *  - `after*` hooks hold an `ErrorResponseData` (short-circuit) or `null`.
 */
final class ErroringPawapayService extends PawapayService
{
    public ?ErrorResponseData $beforeInitiateDepositError = null;

    public ?InitiateDepositRecord $beforeInitiateDepositRecord = null;

    public ?ErrorResponseData $afterInitiateDepositError = null;

    public ?ErrorResponseData $beforeCheckDepositStatusError = null;

    public ?CheckDepositStatusRecord $beforeCheckDepositStatusRecord = null;

    public ?ErrorResponseData $afterCheckDepositStatusError = null;

    public ?ErrorResponseData $beforeResendDepositCallbackError = null;

    public ?ResendDepositCallbackRecord $beforeResendDepositCallbackRecord = null;

    public ?ErrorResponseData $afterResendDepositCallbackError = null;

    public ?ErrorResponseData $beforeCreatePaymentPageError = null;

    public ?CreatePaymentPageRecord $beforeCreatePaymentPageRecord = null;

    public ?ErrorResponseData $afterCreatePaymentPageError = null;

    public ?ErrorResponseData $beforePredictProviderError = null;

    public ?PredictProviderRecord $beforePredictProviderRecord = null;

    public ?ErrorResponseData $afterPredictProviderError = null;

    public function __construct(PawapayClientInterface $client)
    {
        parent::__construct($client);
    }

    /**
     * {@inheritDoc}
     */
    protected function beforeInitiateDeposit(InitiateDepositRecord $record): InitiateDepositRecord|ErrorResponseData
    {
        if ($this->beforeInitiateDepositError !== null) {
            return $this->beforeInitiateDepositError;
        }

        return $this->beforeInitiateDepositRecord ?? $record;
    }

    /**
     * {@inheritDoc}
     */
    protected function afterInitiateDeposit(InitiateDepositRecord $record, InitiateDepositData $data): ?ErrorResponseData
    {
        return $this->afterInitiateDepositError;
    }

    /**
     * {@inheritDoc}
     */
    protected function beforeCheckDepositStatus(CheckDepositStatusRecord $record): CheckDepositStatusRecord|ErrorResponseData
    {
        if ($this->beforeCheckDepositStatusError !== null) {
            return $this->beforeCheckDepositStatusError;
        }

        return $this->beforeCheckDepositStatusRecord ?? $record;
    }

    /**
     * {@inheritDoc}
     */
    protected function afterCheckDepositStatus(CheckDepositStatusRecord $record, CheckDepositStatusData $data): ?ErrorResponseData
    {
        return $this->afterCheckDepositStatusError;
    }

    /**
     * {@inheritDoc}
     */
    protected function beforeResendDepositCallback(ResendDepositCallbackRecord $record): ResendDepositCallbackRecord|ErrorResponseData
    {
        if ($this->beforeResendDepositCallbackError !== null) {
            return $this->beforeResendDepositCallbackError;
        }

        return $this->beforeResendDepositCallbackRecord ?? $record;
    }

    /**
     * {@inheritDoc}
     */
    protected function afterResendDepositCallback(ResendDepositCallbackRecord $record, ResendDepositCallbackData $data): ?ErrorResponseData
    {
        return $this->afterResendDepositCallbackError;
    }

    /**
     * {@inheritDoc}
     */
    protected function beforeCreatePaymentPage(CreatePaymentPageRecord $record): CreatePaymentPageRecord|ErrorResponseData
    {
        if ($this->beforeCreatePaymentPageError !== null) {
            return $this->beforeCreatePaymentPageError;
        }

        return $this->beforeCreatePaymentPageRecord ?? $record;
    }

    /**
     * {@inheritDoc}
     */
    protected function afterCreatePaymentPage(CreatePaymentPageRecord $record, CreatePaymentPageData $data): ?ErrorResponseData
    {
        return $this->afterCreatePaymentPageError;
    }

    /**
     * {@inheritDoc}
     */
    protected function beforePredictProvider(PredictProviderRecord $record): PredictProviderRecord|ErrorResponseData
    {
        if ($this->beforePredictProviderError !== null) {
            return $this->beforePredictProviderError;
        }

        return $this->beforePredictProviderRecord ?? $record;
    }

    /**
     * {@inheritDoc}
     */
    protected function afterPredictProvider(PredictProviderRecord $record, PredictProviderData $data): ?ErrorResponseData
    {
        return $this->afterPredictProviderError;
    }
}
