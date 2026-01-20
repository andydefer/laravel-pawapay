<?php

declare(strict_types=1);

namespace Pawapay\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Pawapay\Contracts\PawapayClientInterface;
use Pawapay\Data\PaymentPage\AmountDetailsData;
use Pawapay\Data\PaymentPage\PaymentPageErrorResponseData;
use Pawapay\Data\PaymentPage\PaymentPageRequestData;
use Pawapay\Data\PaymentPage\PaymentPageSuccessResponseData;
use Pawapay\Data\Responses\PredictProviderFailureResponse;
use Pawapay\Data\Responses\PredictProviderSuccessResponse;
use Pawapay\Enums\PawaPayEndpoint;
use Pawapay\Enums\Language;
use Pawapay\Enums\SupportedCountry;
use Spatie\LaravelData\Optional;

/**
 * Service for interacting with the PawaPay API.
 *
 * @package Pawapay\Services
 */
class PawapayService
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
        } catch (RequestException $e) {
            Log::error('PawaPay Payment Page API exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $data = json_decode($e->response->body(), true);
            return $this->handlePaymentPageResponse($data);
        }
    }

    /**
     * Prepare payload for API request
     */
    private function preparePayload(PaymentPageRequestData $requestData): array
    {
        return  [
            'depositId' => $requestData->depositId,
            'returnUrl' => $requestData->returnUrl,
            'customerMessage' => $requestData->customerMessage,
            'amountDetails' => [
                'amount' => $requestData->amountDetails->amount,
                'currency' => $requestData->amountDetails->currency->value
            ],
            'phoneNumber' =>  $requestData->phoneNumber,
            'language' => $requestData->language->value,
            'country' =>  $requestData->country->value,
            'reason' => $requestData->reason,
            'metadata' =>  $requestData->metadata ?? []
        ];
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
        if (array_key_exists('failureReason', $data)) {
            return PaymentPageErrorResponseData::fromArray($data);
        }

        // Unknown response format
        throw new \InvalidArgumentException('Unknown API response format');
    }
    /**
     * Predict provider from phone number
     */
    public function predictProvider(string $phoneNumber): PredictProviderSuccessResponse|PredictProviderFailureResponse
    {
        try {
            $response = $this->client->post(
                PawaPayEndpoint::PREDICT_PROVIDER,
                ['phoneNumber' => $phoneNumber]
            );

            $data = $response->json();

            if (!is_array($data)) {
                Log::error('PawaPay API invalid response format', [
                    'response' => $response->body(),
                ]);

                throw new \InvalidArgumentException('Invalid API response format');
            }

            // Déterminer le type de réponse basé sur la structure
            return $this->discriminateResponse($data);
        } catch (\Exception $e) {
            Log::error('PawaPay API exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Détermine le type de réponse basé sur la structure des données
     */
    private function discriminateResponse(array $data): PredictProviderSuccessResponse|PredictProviderFailureResponse
    {
        // Réponse de succès : a les clés provider, phoneNumber, country
        if (isset($data['provider']) && isset($data['phoneNumber']) && isset($data['country'])) {
            return PredictProviderSuccessResponse::fromArray($data);
        }

        // Réponse d'échec : a au moins un des messages d'erreur
        if (isset($data['failureMessage']) || isset($data['failureReason']) || isset($data['failureCode'])) {
            return PredictProviderFailureResponse::fromArray($data);
        }

        // Si aucune structure reconnue
        throw new \InvalidArgumentException('Unrecognized API response format: ' . json_encode($data));
    }
}
