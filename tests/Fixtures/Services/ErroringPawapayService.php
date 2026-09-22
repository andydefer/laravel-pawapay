<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Tests\Fixtures\Services;

use AndyDefer\PhpPawapay\Contracts\PawapayClientInterface;
use AndyDefer\PhpPawapay\Datas\CheckDepositStatusData;
use AndyDefer\PhpPawapay\Datas\CreatePaymentPageData;
use AndyDefer\PhpPawapay\Datas\ErrorResponseData;
use AndyDefer\PhpPawapay\Datas\InitiateDepositData;
use AndyDefer\PhpPawapay\Datas\ResendDepositCallbackData;
use AndyDefer\PhpPawapay\Records\CheckDepositStatusRecord;
use AndyDefer\PhpPawapay\Records\CreatePaymentPageRecord;
use AndyDefer\PhpPawapay\Records\InitiateDepositRecord;
use AndyDefer\PhpPawapay\Records\ResendDepositCallbackRecord;
use AndyDefer\PhpPawapay\Services\PawapayService;

/**
 * Test fixture that lets tests force a specific hook to return an error.
 *
 * Each public property holds an optional {@see ErrorResponseData}. When set,
 * the corresponding hook returns it, short-circuiting the flow and letting
 * the caller observe the error propagation.
 */
final class ErroringPawapayService extends PawapayService
{
    public ?ErrorResponseData $beforeInitiateDepositError = null;

    public ?ErrorResponseData $afterInitiateDepositError = null;

    public ?ErrorResponseData $beforeCheckDepositStatusError = null;

    public ?ErrorResponseData $afterCheckDepositStatusError = null;

    public ?ErrorResponseData $beforeResendDepositCallbackError = null;

    public ?ErrorResponseData $afterResendDepositCallbackError = null;

    public ?ErrorResponseData $beforeCreatePaymentPageError = null;

    public ?ErrorResponseData $afterCreatePaymentPageError = null;

    public function __construct(PawapayClientInterface $client)
    {
        parent::__construct($client);
    }

    /**
     * {@inheritDoc}
     */
    protected function beforeInitiateDeposit(InitiateDepositRecord $record): ?ErrorResponseData
    {
        return $this->beforeInitiateDepositError;
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
    protected function beforeCheckDepositStatus(CheckDepositStatusRecord $record): ?ErrorResponseData
    {
        return $this->beforeCheckDepositStatusError;
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
    protected function beforeResendDepositCallback(ResendDepositCallbackRecord $record): ?ErrorResponseData
    {
        return $this->beforeResendDepositCallbackError;
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
    protected function beforeCreatePaymentPage(CreatePaymentPageRecord $record): ?ErrorResponseData
    {
        return $this->beforeCreatePaymentPageError;
    }

    /**
     * {@inheritDoc}
     */
    protected function afterCreatePaymentPage(CreatePaymentPageRecord $record, CreatePaymentPageData $data): ?ErrorResponseData
    {
        return $this->afterCreatePaymentPageError;
    }
}
