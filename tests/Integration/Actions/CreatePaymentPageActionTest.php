<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Tests\Integration\Actions;

use AndyDefer\LaravelPawapay\Http\Actions\CreatePaymentPageAction;
use AndyDefer\LaravelPawapay\Http\Requests\CreatePaymentPageRequest;
use AndyDefer\LaravelPawapay\Tests\IntegrationTestCase;
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

        // Assert: the action returns the failure reason serialized
        $response->assertStatus(200);
        $response->assertJson([
            'hasFailureReason' => true,
            'failureReason' => [
                'failureCode' => 'INVALID_CURRENCY',
                'failureMessage' => "The currency 'EUR' is not supported with provider 'VODACOM_MPESA_COD'.",
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
}
