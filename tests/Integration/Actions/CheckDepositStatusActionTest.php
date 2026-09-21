<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Tests\Integration\Actions;

use AndyDefer\LaravelPawapay\Http\Actions\CheckDepositStatusAction;
use AndyDefer\LaravelPawapay\Http\Requests\CheckDepositStatusRequest;
use AndyDefer\LaravelPawapay\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Route;

final class CheckDepositStatusActionTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/api/check-status-action', action_route(
            CheckDepositStatusRequest::class,
            CheckDepositStatusAction::class,
        ));
    }

    public function test_it_returns_200_with_found_deposit_data(): void
    {
        // Arrange: enqueue a canned 200 response for a found deposit
        $this->client->addDepositFoundResponse([
            'depositId' => 'f4401bd2-1568-4140-bf2d-eb77d2b2b639',
            'status' => 'COMPLETED',
            'amount' => '15.00',
            'currency' => 'ZMW',
            'country' => 'ZMB',
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => '260763456789',
                    'provider' => 'MTN_MOMO_ZMB',
                ],
            ],
            'created' => '2020-10-19T11:17:01Z',
        ]);

        // Act: send a valid payload to the registered route
        $response = $this->postJson('/api/check-status-action', [
            'deposit_id' => 'f4401bd2-1568-4140-bf2d-eb77d2b2b639',
        ]);

        // Assert: the action returns the CheckDepositStatusData serialized
        $response->assertStatus(200);
        $response->assertJson([
            'searchStatus' => 'FOUND',
            'isFound' => true,
            'isNotFound' => false,
            'hasFailureReason' => false,
        ]);
    }

    public function test_it_returns_200_with_not_found_status(): void
    {
        // Arrange: enqueue a canned 200 response for a not found deposit
        $this->client->addNotFoundResponse();

        // Act: send a valid payload to the registered route
        $response = $this->postJson('/api/check-status-action', [
            'deposit_id' => 'f4401bd2-1568-4140-bf2d-eb77d2b2b639',
        ]);

        // Assert: the action returns the not found status serialized
        $response->assertStatus(200);
        $response->assertJson([
            'searchStatus' => 'NOT_FOUND',
            'depositData' => null,
            'isFound' => false,
            'isNotFound' => true,
            'hasFailureReason' => false,
        ]);
    }

    public function test_it_returns_422_when_payload_is_invalid(): void
    {
        // Arrange: nothing to set up, the route is registered in setUp()

        // Act: send an invalid payload (missing deposit_id)
        $response = $this->postJson('/api/check-status-action', []);

        // Assert: validation fails before the action runs
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['deposit_id']);
    }
}
