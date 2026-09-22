<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Tests\Integration\Actions;

use AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface;
use AndyDefer\LaravelPawapay\Http\Actions\InitiateDepositAction;
use AndyDefer\LaravelPawapay\Http\Requests\InitiateDepositRequest;
use AndyDefer\LaravelPawapay\Tests\Fixtures\Services\ErroringPawapayService;
use AndyDefer\LaravelPawapay\Tests\IntegrationTestCase;
use AndyDefer\PhpPawapay\Collections\CountryCollection;
use AndyDefer\PhpPawapay\Collections\CurrencyCollection;
use AndyDefer\PhpPawapay\Collections\LanguageCollection;
use AndyDefer\PhpPawapay\Collections\PayerTypeCollection;
use AndyDefer\PhpPawapay\Collections\ProviderCollection;
use AndyDefer\PhpPawapay\Datas\ErrorResponseData;
use AndyDefer\PhpPawapay\Enums\Country;
use AndyDefer\PhpPawapay\Enums\Currency;
use AndyDefer\PhpPawapay\Enums\Language;
use AndyDefer\PhpPawapay\Enums\PawaPayBaseUrl;
use AndyDefer\PhpPawapay\Enums\PayerType;
use AndyDefer\PhpPawapay\Enums\Provider;
use AndyDefer\PhpPawapay\Services\PawapayService;
use AndyDefer\PhpVo\Enums\HttpStatusCode;
use Illuminate\Support\Facades\Route;

final class InitiateDepositActionTest extends IntegrationTestCase
{
    private const VALID_PAYLOAD = [
        'deposit_id' => 'f4401bd2-1568-4140-bf2d-eb77d2b2b639',
        'phone_number' => '260763456789',
        'provider' => 'MTN_MOMO_ZMB',
        'amount' => 15.00,
        'currency' => 'ZMW',
        'payer_type' => 'MMO',
        'client_reference_id' => 'INV-123456',
        'customer_message' => 'Payment order',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/api/initiate-deposit-action', action_route(
            InitiateDepositRequest::class,
            InitiateDepositAction::class,
        ));
    }

    public function test_it_returns_200_with_initiate_deposit_data(): void
    {
        // Arrange: enqueue a canned 200 response for the underlying HTTP client
        $this->client->addSuccessResponse([
            'depositId' => 'f4401bd2-1568-4140-bf2d-eb77d2b2b639',
            'status' => 'ACCEPTED',
            'created' => '2020-10-19T11:17:01Z',
        ]);

        // Act: send a valid payload to the registered route
        $response = $this->postJson('/api/initiate-deposit-action', self::VALID_PAYLOAD);

        // Assert: the action returns the InitiateDepositData serialized
        $response->assertStatus(200);
        $response->assertJson([
            'depositId' => 'f4401bd2-1568-4140-bf2d-eb77d2b2b639',
            'status' => 'ACCEPTED',
            'created' => '2020-10-19T11:17:01Z',
            'isAccepted' => true,
            'isRejected' => false,
            'isDuplicateIgnored' => false,
            'hasFailureReason' => false,
        ]);
    }

    public function test_it_returns_200_with_rejected_deposit_and_failure_reason(): void
    {
        // Arrange: enqueue a canned 200 response carrying a failure reason
        $this->client->addSuccessResponse([
            'depositId' => null,
            'status' => 'REJECTED',
            'failureReason' => [
                'failureCode' => 'INVALID_PHONE_NUMBER',
                'failureMessage' => "The phone number '2607634' seems to be invalid for the provider 'MTN_MOMO_ZMB'.",
            ],
        ]);

        // Act: send a valid payload to the registered route
        $response = $this->postJson('/api/initiate-deposit-action', self::VALID_PAYLOAD);

        // Assert: the action returns the failure reason serialized
        $response->assertStatus(200);
        $response->assertJson([
            'depositId' => null,
            'status' => 'REJECTED',
            'isAccepted' => false,
            'isRejected' => true,
            'hasFailureReason' => true,
            'failureReason' => [
                'failureCode' => 'INVALID_PHONE_NUMBER',
                'failureMessage' => "The phone number '2607634' seems to be invalid for the provider 'MTN_MOMO_ZMB'.",
            ],
        ]);
    }

    public function test_it_returns_200_with_authentication_error(): void
    {
        // Arrange: enqueue a canned 401 response from PawaPay
        $this->client->addAuthenticationErrorResponse();

        // Act: send a valid payload to the registered route
        $response = $this->postJson('/api/initiate-deposit-action', self::VALID_PAYLOAD);

        // Assert: the action serializes the failure reason
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'REJECTED',
            'hasFailureReason' => true,
            'failureReason' => [
                'failureCode' => 'AUTHENTICATION_ERROR',
                'failureMessage' => 'The API token in the request is invalid.',
            ],
        ]);
    }

    public function test_it_returns_422_when_payload_is_invalid(): void
    {
        // Arrange: nothing to set up, the route is registered in setUp()

        // Act: send an invalid payload (missing deposit_id)
        $response = $this->postJson('/api/initiate-deposit-action', [
            'phone_number' => '260763456789',
        ]);

        // Assert: validation fails before the action runs
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['deposit_id']);
    }

    public function test_it_returns_422_when_provider_is_not_allowed_by_config(): void
    {
        // Arrange: restrict the config so only VODACOM_MPESA_COD is allowed
        $this->app->instance(PawapayConfigInterface::class, new class implements PawapayConfigInterface
        {
            public function getApiToken(): string
            {
                return 'test-token';
            }

            public function getBaseUrl(): PawaPayBaseUrl
            {
                return PawaPayBaseUrl::SANDBOX;
            }

            public function getServiceFqcn(): string
            {
                return PawapayService::class;
            }

            public function getCurrencies(): CurrencyCollection
            {
                return CurrencyCollection::from([Currency::ZMW]);
            }

            public function getLanguages(): LanguageCollection
            {
                return LanguageCollection::from([Language::EN]);
            }

            public function getCountries(): CountryCollection
            {
                return CountryCollection::from([Country::ZMB]);
            }

            public function getProviders(): ProviderCollection
            {
                return ProviderCollection::from([Provider::VODACOM_MPESA_COD]);
            }

            public function getPayerTypes(): PayerTypeCollection
            {
                return PayerTypeCollection::from([PayerType::MMO]);
            }
        });

        // Act: send a payload with a provider not in the allowed list
        $response = $this->postJson('/api/initiate-deposit-action', self::VALID_PAYLOAD);

        // Assert: validation fails before the action runs
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['provider']);
    }

    public function test_it_returns_403_when_service_returns_error_response_data_from_before_hook(): void
    {
        // Arrange: bind an ErroringPawapayService whose before-hook returns a 403
        $erroring = new ErroringPawapayService($this->client);
        $erroring->beforeInitiateDepositError = ErrorResponseData::from([
            'message' => 'Blocked by hook',
            'status' => HttpStatusCode::FORBIDDEN,
            'errorCode' => 'HOOK_BLOCKED',
        ]);

        $this->app->instance(PawapayService::class, $erroring);

        // Act: send a valid payload to the registered route
        $response = $this->postJson('/api/initiate-deposit-action', self::VALID_PAYLOAD);

        // Assert: the action uses the HTTP status from the ErrorResponseData
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Blocked by hook',
            'errorCode' => 'HOOK_BLOCKED',
        ]);
    }
}
