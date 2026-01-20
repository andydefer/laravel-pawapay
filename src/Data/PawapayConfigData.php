<?php

declare(strict_types=1);

namespace Pawapay\Data;

use Spatie\LaravelData\Data;

class PawapayConfigData extends Data
{
    public function __construct(
        public string $sandboxUrl,
        public string $productionUrl,
        public string $token,
        public int $timeout,
        public int $retryTimes,
        public int $retrySleep,
        public string $environment,
        public array $defaultHeaders,
    ) {}

    public function getBaseUrl(): string
    {
        return $this->environment === 'production'
            ? $this->productionUrl
            : $this->sandboxUrl;
    }

    public function getFullUrl(string $endpoint): string
    {
        return rtrim($this->getBaseUrl(), '/') . '/' . ltrim($endpoint, '/');
    }

    public function getHeaders(): array
    {
        return array_merge($this->defaultHeaders, [
            'Authorization' => "Bearer {$this->token}",
        ]);
    }
}
