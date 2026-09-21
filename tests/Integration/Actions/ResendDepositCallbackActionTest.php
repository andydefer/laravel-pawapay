<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Tests\Integration\Actions;

use AndyDefer\LaravelPawapay\Http\Actions\ResendDepositCallbackAction;
use AndyDefer\LaravelPawapay\Http\Requests\ResendDepositCallbackRequest;
use AndyDefer\LaravelPawapay\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Route;

final class ResendDepositCallbackActionTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/api/resend-callback-action', action_route(
            ResendDepositCallbackRequest::class,
            ResendDepositCallbackAction::class,
        ));
    }

    public function test_it_returns_200_with_accepted_callback(): void
    {
        // Arrange: enqueue a canned 200 response with an accepted callback
        $this->client->addSuccessResponse([
            'depositId' => '9b724dbf-32a7-4e63-96bb-59a4747e43ca',
            'status' => 'ACCEPTED',
        ]);

        // Act: send a valid payload to the registered route
        $response = $this->postJson('/api/resend-callback-action', [
            'deposit_id' => '9b724dbf-32a7-4e63-96bb-59a4747e43ca',
        ]);

        // Assert: the action returns the ResendDepositCallbackData serialized
        $response->assertStatus(200);
        $response->assertJson([
            'depositId' => '9b724dbf-32a7-4e63-96bb-59a4747e43ca',
            'status' => 'ACCEPTED',
            'isAccepted' => true,
            'isRejected' => false,
            'hasFailureReason' => false,
        ]);
    }

    public function test_it_returns_200_with_rejected_callback(): void
    {
        // Arrange: enqueue a canned 200 response with a rejected callback
        $this->client->addSuccessResponse([
            'depositId' => '9b724dbf-32a7-4e63-96bb-59a4747e43ca',
            'status' => 'REJECTED',
            'failureReason' => [
                'failureCode' => 'PAYMENT_NOT_APPROVED',
                'failureMessage' => 'Payout not found',
            ],
        ]);

        // Act: send a valid payload to the registered route
        $response = $this->postJson('/api/resend-callback-action', [
            'deposit_id' => '9b724dbf-32a7-4e63-96bb-59a4747e43ca',
        ]);

        // Assert: the action returns the rejected callback serialized
        $response->assertStatus(200);
        $response->assertJson([
            'depositId' => '9b724dbf-32a7-4e63-96bb-59a4747e43ca',
            'status' => 'REJECTED',
            'isAccepted' => false,
            'isRejected' => true,
            'hasFailureReason' => true,
            'failureReason' => [
                'failureCode' => 'PAYMENT_NOT_APPROVED',
                'failureMessage' => 'Payout not found',
            ],
        ]);
    }

    public function test_it_returns_422_when_payload_is_invalid(): void
    {
        // Arrange: nothing to set up, the route is registered in setUp()

        // Act: send an invalid payload (missing deposit_id)
        $response = $this->postJson('/api/resend-callback-action', []);

        // Assert: validation fails before the action runs
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['deposit_id']);
    }
}
