<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Http\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface;
use AndyDefer\PhpPawapay\Records\CreatePaymentPageRecord;
use Illuminate\Contracts\Container\Container;

/**
 * HTTP action that creates a PawaPay-hosted payment page.
 *
 * Resolves the configured PawaPay service and forwards the validated
 * {@see CreatePaymentPageRecord} to it, returning the resulting data as JSON.
 */
final class CreatePaymentPageAction extends AbstractAction
{
    public function __construct(
        private readonly Container $container,
        private readonly PawapayConfigInterface $config,
    ) {}

    /**
     * Handle the incoming request.
     *
     * @param  AbstractRecord  $request  Must be an instance of {@see CreatePaymentPageRecord}.
     * @return ResponseFactory JSON response wrapping the payment page data.
     */
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var CreatePaymentPageRecord $request */
        $service = $this->container->make($this->config->getServiceFqcn());

        return ResponseFactory::json(
            $service->createPaymentPage($request),
        );
    }
}
