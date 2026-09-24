<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Tests\Fixtures\Services;

use AndyDefer\PhpPawapay\Services\PawapayService;

/**
 * Marker subclass used to assert that the container resolves the service
 * from the `pawapay.service_fqcn` config key rather than from a hardcoded
 * default.
 */
final class CustomPawapayService extends PawapayService {}
