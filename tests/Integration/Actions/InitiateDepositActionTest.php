<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Tests\Integration\Actions;

use AndyDefer\LaravelPawapay\Http\Actions\InitiateDepositAction;
use AndyDefer\LaravelPawapay\Http\Requests\InitiateDepositRequest;
use AndyDefer\LaravelPawapay\Tests\IntegrationTestCase;
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
            'failureReason' => [
                'failureCode' => 'INVALID_PHONE_NUMBER',
                'failureMessage' => "The phone number '2607634' seems to be invalid for the provider 'MTN_MOMO_ZMB'.",
            ],
            'isAccepted' => false,
            'isRejected' => true,
            'hasFailureReason' => true,
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
}
