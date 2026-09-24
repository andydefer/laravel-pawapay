<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Http\Actions;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Utils\EmptyRecord;
use AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface;
use AndyDefer\LaravelPawapay\Http\Records\CallbackRecord;
use AndyDefer\PhpClient\Abstracts\Struct;
use AndyDefer\PhpPawapay\Contracts\Callbacks\HandlesCallbacksInterface;
use AndyDefer\PhpPawapay\Contracts\PawapayInterface;
use AndyDefer\PhpPawapay\Enums\CallbackOperationType;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

/**
 * HTTP action that dispatches an incoming PawaPay callback.
 *
 * Detects the operation type from the payload, hydrates the matching Struct,
 * resolves the configured callback handler and forwards the callback to the
 * PawaPay service. Returns a 204 No Content when the callback is handled.
 */
final class CallbackAction extends AbstractAction
{
    public function __construct(
        private readonly Container $container,
        private readonly PawapayConfigInterface $config,
        private readonly Request $request
    ) {}

    /**
     * Handle the incoming callback.
     *
     * @param  AbstractRecord  $request  Must be an instance of {@see CallbackRecord}.
     * @return ResponseFactory 204 No Content.
     */
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var EmptyRecord $request */
        $payload = action_normalizer_chain(true)->normalize($this->request->all());

        $operation = CallbackOperationType::fromPayload($payload);
        $structClass = $operation->structClass();

        /** @var Struct $struct */
        $struct = $structClass::from($payload);

        /** @var PawapayInterface $service */
        $service = $this->container->make($this->config->getServiceFqcn());

        /** @var HandlesCallbacksInterface $handler */
        $handler = $this->container->make($this->config->getHandleCallbackFqcn());

        $service->handleCallback($struct, $handler);

        return ResponseFactory::noContent();
    }
}
