<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Http\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface;
use AndyDefer\PhpPawapay\Records\InitiateDepositRecord;
use Illuminate\Contracts\Container\Container;

/**
 * HTTP action that initiates a Mobile Money deposit through PawaPay.
 *
 * Resolves the configured PawaPay service and forwards the validated
 * {@see InitiateDepositRecord} to it, returning the resulting data as JSON.
 */
final class InitiateDepositAction extends AbstractAction
{
    public function __construct(
        private readonly Container $container,
        private readonly PawapayConfigInterface $config,
    ) {}

    /**
     * Handle the incoming request.
     *
     * @param  AbstractRecord  $request  Must be an instance of {@see InitiateDepositRecord}.
     * @return ResponseFactory JSON response wrapping the initiated deposit data.
     */
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var InitiateDepositRecord $request */
        $service = $this->container->make($this->config->getServiceFqcn());

        return ResponseFactory::json(
            $service->initiateDeposit($request),
        );
    }
}
