<?php

declare(strict_types=1);

namespace AndyDefer\LaravelPawapay\Tests\Integration\Actions;

use AndyDefer\Actions\Http\Requests\EmptyRequest;
use AndyDefer\LaravelPawapay\Contracts\PawapayConfigInterface;
use AndyDefer\LaravelPawapay\Http\Actions\CallbackAction;
use AndyDefer\LaravelPawapay\Tests\Fixtures\Configs\TestPawapayConfig;
use AndyDefer\LaravelPawapay\Tests\IntegrationTestCase;
use AndyDefer\PhpPawapay\Contracts\Callbacks\HandlesCallbacksInterface;
use AndyDefer\PhpPawapay\Structures\Callbacks\CheckoutCallbackStruct;
use AndyDefer\PhpPawapay\Structures\Callbacks\DepositCallbackStruct;
use AndyDefer\PhpPawapay\Structures\Callbacks\PayoutCallbackStruct;
use AndyDefer\PhpPawapay\Structures\Callbacks\RefundCallbackStruct;
use Illuminate\Support\Facades\Route;

final class CallbackActionTest extends IntegrationTestCase
{
    private const DEPOSIT_PAYLOAD = [
        'depositId' => 'f4401bd2-1568-4140-bf2d-eb77d2b2b639',
        'status' => 'COMPLETED',
        'amount' => '123.00',
        'currency' => 'ZMW',
        'country' => 'ZMB',
        'payer' => [
            'type' => 'MMO',
            'accountDetails' => [
                'phoneNumber' => '260763456789',
                'provider' => 'MTN_MOMO_ZMB',
            ],
        ],
        'customerMessage' => 'To ACME company',
        'clientReferenceId' => 'REF-987654321',
        'created' => '2020-10-19T08:17:01Z',
        'providerTransactionId' => '12356789',
    ];

    private const PAYOUT_PAYLOAD = [
        'payoutId' => '6f53f5f3-2f97-4879-8ed6-50072fe9d2fc',
        'status' => 'COMPLETED',
        'amount' => '100.00',
        'currency' => 'ZMW',
        'recipient' => [
            'type' => 'MMO',
            'accountDetails' => [
                'phoneNumber' => '260973024456',
                'provider' => 'MTN_MOMO_ZMB',
            ],
        ],
        'clientReferenceId' => 'REF-987654321',
        'customerMessage' => 'To ACME company',
        'providerTransactionId' => '12356789',
        'created' => '2025-01-15T10:35:00Z',
    ];

    private const REFUND_PAYLOAD = [
        'refundId' => 'a1b2c3d4-1111-2222-3333-444455556666',
        'status' => 'COMPLETED',
        'amount' => '100.00',
        'currency' => 'ZMW',
        'clientReferenceId' => 'REF-987654321',
        'created' => '2025-01-15T10:35:00Z',
    ];

    private const CHECKOUT_PAYLOAD = [
        'checkoutId' => 'afb57b93-7849-49aa-babb-4c3ccbfe3d79',
        'status' => 'COMPLETED',
        'depositStatus' => 'COMPLETED',
        'deposit' => [
            'depositId' => 'eac4d2f3-cf36-4a24-a9eb-7014c630f8f0',
            'status' => 'COMPLETED',
            'amount' => '100.00',
            'currency' => 'ZMW',
            'country' => 'ZMB',
            'payer' => [
                'type' => 'MMO',
                'accountDetails' => [
                    'phoneNumber' => '260973024434',
                    'provider' => 'MTN_MOMO_ZMB',
                ],
            ],
            'customerMessage' => 'Checkout payment',
            'clientReferenceId' => 'REF-987654321',
            'created' => '2025-01-15T10:35:00Z',
            'providerTransactionId' => '12356789',
        ],
        'depositsHistory' => [],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/api/pawapay-callback-action', action_route(
            EmptyRequest::class,
            CallbackAction::class,
        ))->name('pawapay.callback');
    }

    public function test_it_dispatches_deposit_callback_to_configured_handler(): void
    {
        // Arrange: bind a spy handler via config
        $handler = $this->spyHandler();
        $this->bindHandler($handler::class, $handler);

        // Act: send a valid deposit payload
        $response = $this->postJson('/api/pawapay-callback-action', self::DEPOSIT_PAYLOAD);

        // Assert: HTTP 204 and handler received the deposit struct
        $response->assertStatus(204);
        $this->assertInstanceOf(DepositCallbackStruct::class, $handler->deposit);
        $this->assertSame('f4401bd2-1568-4140-bf2d-eb77d2b2b639', $handler->deposit->depositId->getValue());
        $this->assertNull($handler->payout);
        $this->assertNull($handler->refund);
        $this->assertNull($handler->checkout);
    }

    public function test_it_dispatches_payout_callback_to_configured_handler(): void
    {
        // Arrange
        $handler = $this->spyHandler();
        $this->bindHandler($handler::class, $handler);

        // Act
        $response = $this->postJson('/api/pawapay-callback-action', self::PAYOUT_PAYLOAD);

        // Assert
        $response->assertStatus(204);
        $this->assertInstanceOf(PayoutCallbackStruct::class, $handler->payout);
        $this->assertNull($handler->deposit);
    }

    public function test_it_dispatches_refund_callback_to_configured_handler(): void
    {
        // Arrange
        $handler = $this->spyHandler();
        $this->bindHandler($handler::class, $handler);

        // Act
        $response = $this->postJson('/api/pawapay-callback-action', self::REFUND_PAYLOAD);

        // Assert
        $response->assertStatus(204);
        $this->assertInstanceOf(RefundCallbackStruct::class, $handler->refund);
        $this->assertNull($handler->deposit);
    }

    public function test_it_dispatches_checkout_callback_to_configured_handler(): void
    {
        // Arrange
        $handler = $this->spyHandler();
        $this->bindHandler($handler::class, $handler);

        // Act
        $response = $this->postJson('/api/pawapay-callback-action', self::CHECKOUT_PAYLOAD);

        // Assert
        $response->assertStatus(204);
        $this->assertInstanceOf(CheckoutCallbackStruct::class, $handler->checkout);
        $this->assertNull($handler->deposit);
    }

    public function test_it_prefers_checkout_when_payload_contains_deposit_id(): void
    {
        // Arrange: the checkout payload contains both checkoutId and an inner deposit
        $handler = $this->spyHandler();
        $this->bindHandler($handler::class, $handler);

        // Act
        $response = $this->postJson('/api/pawapay-callback-action', self::CHECKOUT_PAYLOAD);

        // Assert: checkout is preferred over deposit
        $response->assertStatus(204);
        $this->assertInstanceOf(CheckoutCallbackStruct::class, $handler->checkout);
        $this->assertNull($handler->deposit);
    }

    public function test_it_returns_500_when_payload_has_no_discriminating_field(): void
    {
        // Arrange
        $handler = $this->spyHandler();
        $this->bindHandler($handler::class, $handler);

        // Act: send a payload with none of the discriminating fields
        $response = $this->postJson('/api/pawapay-callback-action', [
            'status' => 'COMPLETED',
        ]);

        // Assert: the InvalidArgumentException bubbles up as a 500
        $response->assertStatus(500);
    }

    /**
     * Bind a handler instance into the container and expose its FQCN
     * through a {@see TestPawapayConfig}.
     */
    private function bindHandler(string $fqcn, HandlesCallbacksInterface $handler): void
    {
        $this->app->instance($fqcn, $handler);

        $config = new TestPawapayConfig;
        $config->handleCallbackFqcn = $fqcn;

        $this->app->instance(PawapayConfigInterface::class, $config);
    }

    /**
     * Build a spy handler that captures the struct passed to each method.
     */
    private function spyHandler(): HandlesCallbacksInterface
    {
        return new class implements HandlesCallbacksInterface
        {
            public ?DepositCallbackStruct $deposit = null;

            public ?PayoutCallbackStruct $payout = null;

            public ?RefundCallbackStruct $refund = null;

            public ?CheckoutCallbackStruct $checkout = null;

            public function handleDeposit(DepositCallbackStruct $struct): void
            {
                $this->deposit = $struct;
            }

            public function handlePayout(PayoutCallbackStruct $struct): void
            {
                $this->payout = $struct;
            }

            public function handleRefund(RefundCallbackStruct $struct): void
            {
                $this->refund = $struct;
            }

            public function handleCheckout(CheckoutCallbackStruct $struct): void
            {
                $this->checkout = $struct;
            }
        };
    }
}
