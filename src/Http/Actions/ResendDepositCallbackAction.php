<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Http\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface;
use AndyDefer\PhpPawapay\Records\ResendDepositCallbackRecord;
use Illuminate\Contracts\Container\Container;

/**
 * HTTP action that requests PawaPay to resend the callback of an existing deposit.
 *
 * Resolves the configured PawaPay service and forwards the validated
 * {@see ResendDepositCallbackRecord} to it, returning the resulting data as JSON.
 */
final class ResendDepositCallbackAction extends AbstractAction
{
    public function __construct(
        private readonly Container $container,
        private readonly PawapayConfigInterface $config,
    ) {}

    /**
     * Handle the incoming request.
     *
     * @param  AbstractRecord  $request  Must be an instance of {@see ResendDepositCallbackRecord}.
     * @return ResponseFactory JSON response wrapping the resend result data.
     */
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var ResendDepositCallbackRecord $request */
        $service = $this->container->make($this->config->getServiceFqcn());

        return ResponseFactory::json(
            $service->resendDepositCallback($request),
        );
    }
}
