<?php

namespace PawaPay\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PawaPayClient
{
    protected string $apiKey;
    protected string $baseUrl;
    protected int $timeout;

    public function __construct(
        string $apiKey,
        string $baseUrl,
        int $timeout = 30
    ) {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Create a Pay-in (collect money from a customer).
     */
    public function payIn(array $payload): Response
    {
        return $this->request(
            'POST',
            '/payins',
            $this->withReference($payload)
        );
    }

    /**
     * Create a Pay-out (send money to a customer).
     */
    public function payOut(array $payload): Response
    {
        return $this->request(
            'POST',
            '/payouts',
            $this->withReference($payload)
        );
    }

    /**
     * Verify transaction status.
     */
    public function verify(string $transactionId): Response
    {
        return $this->request(
            'GET',
            "/transactions/{$transactionId}"
        );
    }

    /**
     * Perform HTTP request.
     */
    protected function request(
        string $method,
        string $uri,
        array $data = []
    ): Response {
        return Http::timeout($this->timeout)
            ->withToken($this->apiKey)
            ->acceptJson()
            ->asJson()
            ->{$method}($this->baseUrl . $uri, $data)
            ->throw();
    }

    /**
     * Add unique reference if missing.
     */
    protected function withReference(array $payload): array
    {
        if (!isset($payload['reference'])) {
            $payload['reference'] = (string) Str::uuid();
        }

        return $payload;
    }
}
