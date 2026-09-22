<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Tests\Integration\Actions;

use AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface;
use AndyDefer\LaravelPawapay\Http\Actions\CreatePaymentPageAction;
use AndyDefer\LaravelPawapay\Http\Requests\CreatePaymentPageRequest;
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

final class CreatePaymentPageActionTest extends IntegrationTestCase
{
    private const VALID_PAYLOAD = [
        'deposit_id' => '9b724dbf-32a7-4e63-96bb-59a4747e43ca',
        'return_url' => 'https://merchant.example.com/checkout-result',
        'amount' => 25.50,
        'currency' => 'USD',
        'phone_number' => '243812345678',
        'language' => 'EN',
        'country' => 'COD',
        'customer_message' => 'Payment order',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/api/create-page-action', action_route(
            CreatePaymentPageRequest::class,
            CreatePaymentPageAction::class,
        ));
    }

    public function test_it_returns_200_with_redirect_url(): void
    {
        // Arrange: enqueue a canned 200 response with a redirect URL
        $this->client->addSuccessResponse([
            'redirectUrl' => 'https://sandbox.paywith.pawapay.io/v2?token=xxx',
        ]);

        // Act: send a valid payload to the registered route
        $response = $this->postJson('/api/create-page-action', self::VALID_PAYLOAD);

        // Assert: the action returns the CreatePaymentPageData serialized
        $response->assertStatus(200);
        $response->assertJson([
            'redirectUrl' => 'https://sandbox.paywith.pawapay.io/v2?token=xxx',
            'failureReason' => null,
            'hasFailureReason' => false,
        ]);
    }

    public function test_it_returns_200_with_failure_reason(): void
    {
        // Arrange: enqueue a canned 200 response carrying a failure reason
        $this->client->addSuccessResponse([
            'failureReason' => [
                'failureCode' => 'INVALID_CURRENCY',
                'failureMessage' => "The currency 'EUR' is not supported with provider 'VODACOM_MPESA_COD'.",
            ],
        ]);

        // Act: send a valid payload to the registered route
        $response = $this->postJson('/api/create-page-action', self::VALID_PAYLOAD);

        // Assert: the action serializes the failure reason
        $response->assertStatus(200);
        $response->assertJson([
            'hasFailureReason' => true,
            'failureReason' => [
                'failureCode' => 'INVALID_CURRENCY',
                'failureMessage' => "The currency 'EUR' is not supported with provider 'VODACOM_MPESA_COD'.",
            ],
        ]);
    }

    public function test_it_returns_200_with_authentication_error(): void
    {
        // Arrange: enqueue a canned 401 response from PawaPay
        $this->client->addAuthenticationErrorResponse();

        // Act: send a valid payload to the registered route
        $response = $this->postJson('/api/create-page-action', self::VALID_PAYLOAD);

        // Assert: the action serializes the failure reason
        $response->assertStatus(200);
        $response->assertJson([
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

        // Act: send an invalid payload (missing return_url)
        $response = $this->postJson('/api/create-page-action', [
            'deposit_id' => '9b724dbf-32a7-4e63-96bb-59a4747e43ca',
            'amount' => 25.50,
            'currency' => 'USD',
            'phone_number' => '243812345678',
            'language' => 'EN',
            'country' => 'COD',
        ]);

        // Assert: validation fails before the action runs
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['return_url']);
    }

    public function test_it_returns_422_when_country_is_not_allowed_by_config(): void
    {
        // Arrange: restrict the config so only ZMB is allowed
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
                return CurrencyCollection::from([Currency::USD]);
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

        // Act: send a payload with a country not in the allowed list
        $response = $this->postJson('/api/create-page-action', self::VALID_PAYLOAD);

        // Assert: validation fails before the action runs
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['country']);
    }

    public function test_it_returns_403_when_service_returns_error_response_data_from_before_hook(): void
    {
        // Arrange: bind an ErroringPawapayService whose before-hook returns a 403
        $erroring = new ErroringPawapayService($this->client);
        $erroring->beforeCreatePaymentPageError = ErrorResponseData::from([
            'message' => 'Blocked by hook',
            'status' => HttpStatusCode::FORBIDDEN,
            'errorCode' => 'HOOK_BLOCKED',
        ]);

        $this->app->instance(PawapayService::class, $erroring);

        // Act: send a valid payload to the registered route
        $response = $this->postJson('/api/create-page-action', self::VALID_PAYLOAD);

        // Assert: the action uses the HTTP status from the ErrorResponseData
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Blocked by hook',
            'errorCode' => 'HOOK_BLOCKED',
        ]);
    }
}
