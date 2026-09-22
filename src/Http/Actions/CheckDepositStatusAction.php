<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Http\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface;
use AndyDefer\PhpPawapay\Contracts\PawapayInterface;
use AndyDefer\PhpPawapay\Datas\ErrorResponseData;
use AndyDefer\PhpPawapay\Records\CheckDepositStatusRecord;
use Illuminate\Contracts\Container\Container;

/**
 * HTTP action that retrieves the status of an existing PawaPay deposit.
 *
 * Resolves the configured PawaPay service and forwards the validated
 * {@see CheckDepositStatusRecord} to it, returning the resulting data as JSON.
 * When the service returns an {@see ErrorResponseData}, the action uses the
 * embedded HTTP status code instead of defaulting to 200.
 */
final class CheckDepositStatusAction extends AbstractAction
{
    public function __construct(
        private readonly Container $container,
        private readonly PawapayConfigInterface $config,
    ) {}

    /**
     * Handle the incoming request.
     *
     * @param  AbstractRecord  $request  Must be an instance of {@see CheckDepositStatusRecord}.
     * @return ResponseFactory JSON response wrapping the deposit status data or the error.
     */
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var CheckDepositStatusRecord $request */
        /** @var PawapayInterface $service */
        $service = $this->container->make($this->config->getServiceFqcn());

        $result = $service->checkDepositStatus($request);

        if ($result instanceof ErrorResponseData) {
            return ResponseFactory::json($result, $result->status);
        }

        return ResponseFactory::json($result);
    }
}
