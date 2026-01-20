<?php

declare(strict_types=1);

namespace Pawapay\Services;

use Pawapay\Contracts\PawapayClientInterface;
use Pawapay\Data\PawapayConfigData;
use Pawapay\Enums\PawaPayEndpoint;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class PawapayClient implements PawapayClientInterface
{
    private PendingRequest $httpClient;

    private string $baseUrl;

    public function __construct(PawapayConfigData $config)
    {
        $this->baseUrl = $config->environment === 'production'
            ? $config->productionUrl
            : $config->sandboxUrl;

        $this->httpClient = Http::withHeaders(array_merge(
            $config->defaultHeaders,
            ['Authorization' => 'Bearer ' . $config->token]
        ))
            ->timeout($config->timeout)
            ->retry($config->retryTimes, $config->retrySleep);
    }

    public function post(PawaPayEndpoint $endpoint, array $data = [])
    {
        return $this->httpClient->post(
            $this->buildUrl($endpoint),
            $data
        );
    }

    public function get(PawaPayEndpoint $endpoint, array $query = [], array $parameters = [])
    {
        return $this->httpClient->get(
            $this->buildUrl($endpoint, $parameters),
            $query
        );
    }

    public function put(PawaPayEndpoint $endpoint, array $data = [])
    {
        return $this->httpClient->put(
            $this->buildUrl($endpoint),
            $data
        );
    }

    public function patch(PawaPayEndpoint $endpoint, array $data = [])
    {
        return $this->httpClient->patch(
            $this->buildUrl($endpoint),
            $data
        );
    }

    public function delete(PawaPayEndpoint $endpoint)
    {
        return $this->httpClient->delete($this->buildUrl($endpoint));
    }

    private function buildUrl(PawaPayEndpoint $endpoint, array $parameters = []): string
    {
        $path = $endpoint->value;

        if (str_contains($path, '{') && $parameters !== []) {
            $path = $endpoint->buildPath($parameters);
        }

        return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
    }
}
