<?php

declare(strict_types=1);

namespace Pawapay\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Pawapay\Data\Deposit\InitiateDepositRequestData;
use Pawapay\Data\PaymentPage\PaymentPageRequestData;
use Pawapay\Data\Responses\CheckDepositStatusWrapperData;
use Pawapay\Data\Responses\PredictProviderFailureResponse;
use Pawapay\Data\Responses\PredictProviderSuccessResponse;
use Pawapay\Enums\Currency;
use Pawapay\Enums\Language;
use Pawapay\Enums\SupportedCountry;
use Pawapay\Enums\SupportedProvider;
use Pawapay\Services\PawapayService;

class PawapayController extends Controller
{
    public function __construct(
        private PawapayService $pawapayService
    ) {}

    /**
     * Predict mobile money provider from phone number
     */
    public function predictProvider(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phoneNumber' => ['required', 'string', 'regex:/^(?:\+?\d{1,3}[- ]?)?\d{6,14}$/']
        ]);

        $phoneNumber = $validated['phoneNumber'];

        try {
            $response = $this->pawapayService->predictProvider($phoneNumber);

            $isSuccess = $response instanceof PredictProviderSuccessResponse;

            Log::info('Provider prediction completed', [
                'phoneNumber' => $phoneNumber,
                'success' => $isSuccess,
                'has_provider' => $isSuccess ? $response->provider->value : null,
            ]);

            return response()->json([
                'success' => $isSuccess,
                'data' => $response
            ]);
        } catch (\Exception $e) {
            Log::error('Provider prediction failed', [
                'phoneNumber' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to predict provider',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a payment page
     */
    public function createPaymentPage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'depositId' => ['required', 'uuid', 'max:255'],
            'returnUrl' => ['required', 'url', 'max:500'],
            'customerMessage' => ['nullable', 'string', 'max:500'],
            'amountDetails.amount' => ['required', 'string', 'regex:/^\d+(\.\d{1,2})?$/'],
            'amountDetails.currency' => ['required', Rule::enum(Currency::class)],
            'phoneNumber' => ['nullable', 'string', 'regex:/^(?:\+?\d{1,3}[- ]?)?\d{6,14}$/'],
            'language' => ['nullable', Rule::enum(Language::class)],
            'country' => ['nullable', Rule::enum(SupportedCountry::class)],
            'reason' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array', 'max:10'],
            'metadata.*' => ['required', 'array'],
            'metadata.*.*' => ['required', 'string'],
        ]);

        try {
            $requestData = PaymentPageRequestData::fromArray($validated);
            $response = $this->pawapayService->createPaymentPage($requestData);

            $isSuccess = !($response instanceof \Pawapay\Data\PaymentPage\PaymentPageErrorResponseData);

            Log::info('Payment page creation completed', [
                'depositId' => $validated['depositId'],
                'success' => $isSuccess,
            ]);

            return response()->json([
                'success' => $isSuccess,
                'data' => $response
            ]);
        } catch (\Exception $e) {
            Log::error('Payment page creation failed', [
                'depositId' => $validated['depositId'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to create payment page',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initiate a direct deposit
     */
    public function initiateDeposit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'depositId' => ['required', 'uuid', 'max:255'],
            'payer.type' => ['required', 'string', 'in:MMO'],
            'payer.accountDetails.phoneNumber' => ['required', 'string', 'regex:/^(?:\+?\d{1,3}[- ]?)?\d{6,14}$/'],
            'payer.accountDetails.provider' => ['required', Rule::enum(SupportedProvider::class)],
            'amount' => ['required', 'string', 'regex:/^\d+(\.\d{1,2})?$/'],
            'currency' => ['required', Rule::enum(Currency::class)],
            'preAuthorisationCode' => ['nullable', 'string', 'max:255'],
            'clientReferenceId' => ['nullable', 'string', 'max:255'],
            'customerMessage' => ['nullable', 'string', 'max:500'],
            'metadata' => ['nullable', 'array', 'max:10'],
            'metadata.*' => ['required', 'array'],
            'metadata.*.*' => ['required', 'string'],
        ]);

        try {
            $requestData = InitiateDepositRequestData::fromArray($validated);
            $response = $this->pawapayService->initiateDeposit($requestData);

            $isSuccess = $response->isAccepted() || $response->isDuplicateIgnored();

            Log::info('Deposit initiation completed', [
                'depositId' => $validated['depositId'],
                'success' => $isSuccess,
                'status' => $response->status->value,
            ]);

            return response()->json([
                'success' => $isSuccess,
                'data' => $response
            ]);
        } catch (\Exception $e) {
            Log::error('Deposit initiation failed', [
                'depositId' => $validated['depositId'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to initiate deposit',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check deposit status
     */
    public function checkDepositStatus(string $depositId): JsonResponse
    {
        $validated = validator(['depositId' => $depositId], [
            'depositId' => ['required', 'uuid', 'max:255']
        ])->validate();

        $depositId = $validated['depositId'];

        try {
            $response = $this->pawapayService->checkDepositStatus($depositId);

            $isFound = $response->isFound();

            Log::info('Deposit status check completed', [
                'depositId' => $depositId,
                'found' => $isFound,
                'status' => $response->status->value,
            ]);

            return response()->json([
                'success' => $isFound,
                'data' => $response
            ]);
        } catch (\Exception $e) {
            Log::error('Deposit status check failed', [
                'depositId' => $depositId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to check deposit status',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
