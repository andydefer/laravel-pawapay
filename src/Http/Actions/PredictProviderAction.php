<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Http\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface;
use AndyDefer\PhpPawapay\Contracts\PawapayInterface;
use AndyDefer\PhpPawapay\Datas\ErrorResponseData;
use AndyDefer\PhpPawapay\Records\PredictProviderRecord;
use AndyDefer\PhpPawapay\Services\PawapayService;
use Illuminate\Contracts\Container\Container;

/**
 * HTTP action that predicts the Mobile Money provider associated with a phone number.
 *
 * Resolves the configured PawaPay service and forwards the validated
 * {@see PredictProviderRecord} to it, returning the resulting data as JSON.
 * When the service returns an {@see ErrorResponseData}, the action uses the
 * embedded HTTP status code instead of defaulting to 200.
 */
final class PredictProviderAction extends AbstractAction
{
    public function __construct(
        private readonly Container $container,
        private readonly PawapayConfigInterface $config,
    ) {}

    /**
     * Handle the incoming request.
     *
     * @param  AbstractRecord  $request  Must be an instance of {@see PredictProviderRecord}.
     * @return ResponseFactory JSON response wrapping the prediction or the error.
     */
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var PredictProviderRecord $request */
        /** @var PawapayInterface $service */
        $service = $this->container->make($this->config->getServiceFqcn());
        PawapayService::class;

        $result = $service->predictProvider($request);

        if ($result instanceof ErrorResponseData) {
            return ResponseFactory::json($result, $result->status);
        }

        return ResponseFactory::json($result);
    }
}
