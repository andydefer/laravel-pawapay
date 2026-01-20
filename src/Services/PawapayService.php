<?php

declare(strict_types=1);

namespace Pawapay\Services;

use InvalidArgumentException;
use Exception;
use Pawapay\Enums\TransactionStatus;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Pawapay\Contracts\PawapayClientInterface;
use Pawapay\Data\Deposit\InitiateDepositRequestData;
use Pawapay\Data\Deposit\InitiateDepositResponseData;
use Pawapay\Data\PaymentPage\PaymentPageErrorResponseData;
use Pawapay\Data\PaymentPage\PaymentPageRequestData;
use Pawapay\Data\PaymentPage\PaymentPageSuccessResponseData;
use Pawapay\Data\Responses\CheckDepositStatusWrapperData;
use Pawapay\Data\Responses\PredictProviderFailureResponse;
use Pawapay\Data\Responses\PredictProviderSuccessResponse;
use Pawapay\Enums\PawaPayEndpoint;
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
                throw new InvalidArgumentException('Invalid API response format');
            }

            return $this->handlePaymentPageResponse($data);
        } catch (RequestException $requestException) {
            Log::error('PawaPay Payment Page API exception', [
                'message' => $requestException->getMessage(),
                'trace' => $requestException->getTraceAsString(),
            ]);
            $data = json_decode($requestException->response->body(), true);
            return $this->handlePaymentPageResponse($data);
        }
    }


    /**
     * Initiate a deposit (direct payment request)
     */
    public function initiateDeposit(InitiateDepositRequestData $requestData): InitiateDepositResponseData
    {
        try {
            $payload = $this->prepareDepositPayload($requestData);

            $response = $this->client->post(
                PawaPayEndpoint::INITIATE_DEPOSIT,
                $payload
            );

            $data = $response->json();

            if (!is_array($data)) {
                Log::error('PawaPay API invalid response format for deposit initiation', [
                    'depositId' => $requestData->depositId,
                    'response' => $response->body(),
                ]);

                throw new InvalidArgumentException('Invalid API response format');
            }

            return InitiateDepositResponseData::fromApiResponse($data);
        } catch (RequestException $e) {
            Log::error('PawaPay API exception for deposit initiation', [
                'depositId' => $requestData->depositId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Si l'API retourne une réponse même en cas d'erreur
            if ($e->response) {
                $data = json_decode($e->response->body(), true);
                if (is_array($data) && isset($data['status'])) {
                    return InitiateDepositResponseData::fromApiResponse($data);
                }
            }

            throw $e;
        } catch (Exception $e) {
            Log::error('PawaPay API exception for deposit initiation', [
                'depositId' => $requestData->depositId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Prepare payload for deposit initiation API request
     */
    private function prepareDepositPayload(InitiateDepositRequestData $requestData): array
    {
        $payload = [
            'depositId' => $requestData->depositId,
            'payer' => [
                'type' => $requestData->payer->type,
                'accountDetails' => [
                    'phoneNumber' => $requestData->payer->accountDetails->phoneNumber,
                    'provider' => $requestData->payer->accountDetails->provider->value,
                ],
            ],
            'amount' => $requestData->amount,
            'currency' => $requestData->currency->value,
        ];

        // Ajouter les champs optionnels s'ils sont présents
        if (!$requestData->preAuthorisationCode instanceof Optional) {
            $payload['preAuthorisationCode'] = $requestData->preAuthorisationCode;
        }

        if (!$requestData->clientReferenceId instanceof Optional) {
            $payload['clientReferenceId'] = $requestData->clientReferenceId;
        }

        if (!$requestData->customerMessage instanceof Optional) {
            $payload['customerMessage'] = $requestData->customerMessage;
        }

        if (!$requestData->metadata instanceof Optional && $requestData->metadata !== []) {
            $payload['metadata'] = $requestData->metadata;
        }

        return $payload;
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
     * @param array<string, mixed> $data
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
        throw new InvalidArgumentException('Unknown API response format');
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

                throw new InvalidArgumentException('Invalid API response format');
            }

            // Déterminer le type de réponse basé sur la structure
            return $this->discriminateResponse($data);
        } catch (Exception $exception) {
            Log::error('PawaPay API exception', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }

    /**
     * Détermine le type de réponse basé sur la structure des données
     * @param array<string, mixed> $data
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
        throw new InvalidArgumentException('Unrecognized API response format: ' . json_encode($data));
    }

    /**
     * Check deposit status by deposit ID
     */
    public function checkDepositStatus(string $depositId): CheckDepositStatusWrapperData
    {
        try {
            $response = $this->client->get(
                PawaPayEndpoint::CHECK_DEPOSIT_STATUS,
                [],
                ['depositId' => $depositId]
            );

            $data = $response->json();

            if (!is_array($data)) {
                Log::error('PawaPay API invalid response format for deposit status', [
                    'depositId' => $depositId,
                    'response' => $response->body(),
                ]);

                throw new InvalidArgumentException('Invalid API response format');
            }

            return CheckDepositStatusWrapperData::fromApiResponse($data);
        } catch (RequestException $e) {
            Log::error('PawaPay API exception for deposit status', [
                'depositId' => $depositId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Si l'API retourne 404 pour un dépôt non trouvé
            if ($e->response && $e->response->status() === 404) {
                return new CheckDepositStatusWrapperData(
                    status: TransactionStatus::NOT_FOUND,
                    data: Optional::create(),
                );
            }

            throw $e;
        } catch (Exception $e) {
            Log::error('PawaPay API exception for deposit status', [
                'depositId' => $depositId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
