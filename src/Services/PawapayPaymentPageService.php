<?php

declare(strict_types=1);

namespace Pawapay\Services;

use Pawapay\Contracts\PawapayClientInterface;
use Pawapay\Data\PaymentPage\AmountDetailsData;
use Pawapay\Data\PaymentPage\PaymentPageErrorResponseData;
use Pawapay\Data\PaymentPage\PaymentPageRequestData;
use Pawapay\Data\PaymentPage\PaymentPageSuccessResponseData;
use Pawapay\Enums\Currency;
use Pawapay\Enums\PawaPayEndpoint;
use Pawapay\Enums\Language;
use Pawapay\Enums\SupportedCountry;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelData\Optional;

class PawapayPaymentPageService
{
    public function __construct(
        private PawapayClientInterface $client
    ) {}

    /**
     * Create a payment page session
     */
    public function createPaymentPage(PaymentPageRequestData $requestData): PaymentPageSuccessResponseData|PaymentPageErrorResponseData
    {
        try {
            $payload = $this->preparePayload($requestData);

            $response = $this->client->post(
                PawaPayEndpoint::PAYMENT_PAGE,
                $payload
            );

            $data = $response->json();

            if (!is_array($data)) {
                throw new \InvalidArgumentException('Invalid API response format');
            }

            return $this->handlePaymentPageResponse($data);
        } catch (\Exception $e) {
            Log::error('PawaPay Payment Page API exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Prepare payload for API request
     */
    private function preparePayload(PaymentPageRequestData $requestData): array
    {
        $payload = $requestData->toArray();

        // Transform metadata items
        if (isset($payload['metadata']) && is_array($payload['metadata'])) {
            $payload['metadata'] = array_map(
                fn($item) => $item['data'],
                $payload['metadata']
            );
        }

        // Transform enums to strings
        if ($requestData->language instanceof Language && !$requestData->language instanceof Optional) {
            $payload['language'] = $requestData->language->value;
        }

        if ($requestData->country instanceof SupportedCountry && !$requestData->country instanceof Optional) {
            $payload['country'] = $requestData->country->value;
        }

        if ($requestData->amountDetails instanceof AmountDetailsData && !$requestData->amountDetails instanceof Optional) {
            $payload['amountDetails']['currency'] = $requestData->amountDetails->currency->value;
        }

        return $payload;
    }

    /**
     * Handle API response
     */
    private function handlePaymentPageResponse(array $data): PaymentPageSuccessResponseData|PaymentPageErrorResponseData
    {
        // Success response
        if (isset($data['redirectUrl'])) {
            return PaymentPageSuccessResponseData::fromArray($data);
        }

        // Error response
        if (isset($data['status']) && $data['status'] === 'REJECTED') {
            return PaymentPageErrorResponseData::fromArray($data);
        }

        // Unknown response format
        throw new \InvalidArgumentException('Unknown API response format');
    }

    /**
     * Get the HTTP client instance
     */
    public function getClient(): PawapayClientInterface
    {
        return $this->client;
    }
}
