<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Tests\Integration\Actions;

use AndyDefer\LaravelPawapay\Http\Actions\PredictProviderAction;
use AndyDefer\LaravelPawapay\Http\Requests\PredictProviderRequest;
use AndyDefer\LaravelPawapay\Tests\Fixtures\Services\ErroringPawapayService;
use AndyDefer\LaravelPawapay\Tests\IntegrationTestCase;
use AndyDefer\PhpPawapay\Datas\ErrorResponseData;
use AndyDefer\PhpPawapay\Enums\Country;
use AndyDefer\PhpPawapay\Enums\Provider;
use AndyDefer\PhpPawapay\Services\PawapayService;
use AndyDefer\PhpVo\Enums\HttpStatusCode;
use Illuminate\Support\Facades\Route;

final class PredictProviderActionTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/api/predict-provider-action', action_route(
            PredictProviderRequest::class,
            PredictProviderAction::class,
        ))->name('pawapay.predict-provider');
    }

    public function test_it_returns_200_with_prediction_data(): void
    {
        // Arrange: enqueue a canned 200 response identifying the provider
        $this->client->addSuccessResponse([
            'country' => 'ZMB',
            'provider' => 'MTN_MOMO_ZMB',
            'phoneNumber' => '260763456789',
        ]);

        // Act: send a valid payload to the registered route
        $response = $this->postJson('/api/predict-provider-action', [
            'phone_number' => '260763456789',
        ]);

        // Assert: the action returns the PredictProviderData serialized
        $response->assertStatus(200);
        $response->assertJson([
            'country' => 'ZMB',
            'provider' => 'MTN_MOMO_ZMB',
            'phoneNumber' => '260763456789',
            'isFound' => true,
            'failureReason' => null,
            'hasFailureReason' => false,
        ]);
    }

    public function test_it_returns_200_with_drc_vodacom_prediction(): void
    {
        // Arrange
        $this->client->addSuccessResponse([
            'country' => 'COD',
            'provider' => 'VODACOM_MPESA_COD',
            'phoneNumber' => '243812345678',
        ]);

        // Act
        $response = $this->postJson('/api/predict-provider-action', [
            'phone_number' => '243812345678',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'country' => 'COD',
            'provider' => 'VODACOM_MPESA_COD',
            'phoneNumber' => '243812345678',
            'isFound' => true,
        ]);
    }

    public function test_it_returns_200_with_not_found_when_provider_unknown(): void
    {
        // Arrange: empty body from PawaPay
        $this->client->addSuccessResponse([]);

        // Act
        $response = $this->postJson('/api/predict-provider-action', [
            'phone_number' => '260763456789',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'country' => null,
            'provider' => null,
            'phoneNumber' => null,
            'isFound' => false,
            'hasFailureReason' => false,
        ]);
    }

    public function test_it_returns_200_with_failure_reason_when_country_unsupported(): void
    {
        // Arrange: PawaPay rejects the country
        $this->client->addSuccessResponse([
            'failureReason' => [
                'failureCode' => 'INVALID_COUNTRY',
                'failureMessage' => 'The country of the provided phone number is not supported.',
            ],
        ]);

        // Act
        $response = $this->postJson('/api/predict-provider-action', [
            'phone_number' => '99999999999',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'isFound' => false,
            'hasFailureReason' => true,
            'failureReason' => [
                'failureCode' => 'INVALID_COUNTRY',
                'failureMessage' => 'The country of the provided phone number is not supported.',
            ],
        ]);
    }

    public function test_it_returns_401_when_authentication_fails(): void
    {
        // Arrange
        $this->client->addAuthenticationErrorResponse();

        // Act
        $response = $this->postJson('/api/predict-provider-action', [
            'phone_number' => '260763456789',
        ]);

        // Assert: the action propagates the HTTP status from ErrorResponseData
        $response->assertStatus(401);
        $response->assertJson([
            'status' => 401,
            'errorCode' => 'AUTHENTICATION_ERROR',
        ]);
    }

    public function test_it_returns_403_when_service_returns_error_response_data_from_before_hook(): void
    {
        // Arrange: bind an ErroringPawapayService whose before-hook returns a 403
        $erroring = new ErroringPawapayService($this->client);
        $erroring->beforePredictProviderError = ErrorResponseData::from([
            'message' => 'Prediction blocked',
            'status' => HttpStatusCode::FORBIDDEN,
            'errorCode' => 'PREDICT_BLOCKED',
        ]);

        $this->app->instance(PawapayService::class, $erroring);

        // Act
        $response = $this->postJson('/api/predict-provider-action', [
            'phone_number' => '260763456789',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Prediction blocked',
            'errorCode' => 'PREDICT_BLOCKED',
        ]);
    }

    public function test_it_returns_422_when_phone_number_is_missing(): void
    {
        // Act: send an empty payload
        $response = $this->postJson('/api/predict-provider-action', []);

        // Assert: validation rejects before the action runs
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone_number']);
    }

    public function test_it_returns_422_when_phone_number_format_is_invalid(): void
    {
        // Act: send a malformed phone number
        $response = $this->postJson('/api/predict-provider-action', [
            'phone_number' => '+260 763-456789',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone_number']);
    }

    public function test_it_returns_422_when_phone_number_starts_with_zero(): void
    {
        // Act: E.164 without + must not start with 0
        $response = $this->postJson('/api/predict-provider-action', [
            'phone_number' => '0763456789',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone_number']);
    }
}
