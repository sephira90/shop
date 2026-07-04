<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\WebhookReceipt;
use App\Support\Data\TypedValue;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesCatalogVariant;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use CreatesCatalogVariant;
    use RefreshDatabase;

    /**
     * Ensure webhook without signature header is rejected.
     */
    public function test_payment_webhook_requires_signature_header(): void
    {
        $this->postJson('/api/v1/webhooks/payment', [
            'event_id' => 'evt-missing-signature',
            'transaction_id' => 'tx-missing-signature',
            'status' => 'paid',
        ])
            ->assertBadRequest()
            ->assertJsonPath('error.message', 'Missing X-Signature header.')
            ->assertJsonPath('error.type', 'BadRequestHttpException')
            ->assertJsonPath('error.code', 'domain_failure');
    }

    /**
     * Ensure payment webhook marks order as paid.
     */
    public function test_payment_webhook_processing(): void
    {
        $this->seed([RoleSeeder::class]);

        $user = User::factory()->create();
        $user->assignRole('customer');
        Sanctum::actingAs($user);

        $variant = $this->createActiveVariantWithInventory();

        $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $orderResponse = $this->withHeader('Idempotency-Key', 'payment-order-key')->postJson('/api/v1/checkout/place-order', [
            'email' => $user->email,
            'billing_address' => [
                'line1' => '1 Main Street',
                'city' => 'New York',
                'country' => 'US',
                'postcode' => '10001',
            ],
            'shipping_address' => [
                'line1' => '1 Main Street',
                'city' => 'New York',
                'country' => 'US',
                'postcode' => '10001',
            ],
        ])->assertCreated();

        $orderId = $this->jsonString($orderResponse, 'data.id');

        $this->initiatePayment($orderId, 'payment-initiate-processing');

        $payment = Payment::query()->whereHas('order', static fn ($query) => $query->where('id', $orderId))->firstOrFail();

        $payload = [
            'event_id' => 'evt-paid-1',
            'transaction_id' => $payment->transaction_id,
            'status' => 'paid',
        ];

        $signature = hash('sha256', $payload['event_id']);

        $this->withHeader('X-Signature', $signature)
            ->postJson('/api/v1/webhooks/payment', $payload)
            ->assertAccepted();

        $order = Order::query()->whereKey($orderId)->firstOrFail();
        $this->assertSame('paid', TypedValue::string($order->getRawOriginal('status')));
        $this->assertSame('captured', TypedValue::string($order->getRawOriginal('payment_status')));
    }

    /**
     * Ensure failed payment webhook restores inventory when order is cancelled.
     */
    public function test_payment_failed_webhook_restores_inventory_when_order_is_cancelled(): void
    {
        $this->seed([RoleSeeder::class]);

        $user = User::factory()->create();
        $user->assignRole('customer');
        Sanctum::actingAs($user);

        $variant = $this->createActiveVariantWithInventory(5);

        $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertOk();

        $orderResponse = $this->withHeader('Idempotency-Key', 'payment-failed-release-order-key')->postJson('/api/v1/checkout/place-order', [
            'email' => $user->email,
            'billing_address' => [
                'line1' => '1 Main Street',
                'city' => 'New York',
                'country' => 'US',
                'postcode' => '10001',
            ],
            'shipping_address' => [
                'line1' => '1 Main Street',
                'city' => 'New York',
                'country' => 'US',
                'postcode' => '10001',
            ],
        ])->assertCreated();

        $orderId = $this->jsonString($orderResponse, 'data.id');

        $this->assertSame(3, $variant->inventory()->firstOrFail()->quantity);

        $this->initiatePayment($orderId, 'payment-initiate-failed-release');

        $payment = Payment::query()->whereHas('order', static fn ($query) => $query->where('id', $orderId))->firstOrFail();

        $payload = [
            'event_id' => 'evt-payment-failed-release',
            'transaction_id' => $payment->transaction_id,
            'status' => 'failed',
        ];

        $this->withHeader('X-Signature', hash('sha256', $payload['event_id']))
            ->postJson('/api/v1/webhooks/payment', $payload)
            ->assertAccepted();

        $order = Order::query()->whereKey($orderId)->firstOrFail();

        $this->assertSame('cancelled', TypedValue::string($order->getRawOriginal('status')));
        $this->assertSame('failed', TypedValue::string($order->getRawOriginal('payment_status')));
        $this->assertSame(5, $variant->inventory()->firstOrFail()->quantity);
    }

    /**
     * Ensure replay of same event payload is idempotent.
     */
    public function test_payment_webhook_replay_with_same_event_id_is_idempotent(): void
    {
        [$order, $payment] = $this->createPaidOrderWithPayment('payment-replay-order-key');

        $eventId = 'evt-payment-replay';
        $payload = [
            'event_id' => $eventId,
            'transaction_id' => $payment->transaction_id,
            'status' => 'paid',
        ];

        $this->withHeader('X-Signature', hash('sha256', $eventId))
            ->postJson('/api/v1/webhooks/payment', $payload)
            ->assertAccepted();

        $this->withHeader('X-Signature', hash('sha256', $eventId))
            ->postJson('/api/v1/webhooks/payment', $payload)
            ->assertAccepted();

        $order = $order->fresh();
        $payment = $payment->fresh();

        $this->assertInstanceOf(Order::class, $order);
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertSame('paid', TypedValue::string($order->getRawOriginal('status')));
        $this->assertSame('captured', TypedValue::string($order->getRawOriginal('payment_status')));
        $this->assertSame('captured', TypedValue::string($payment->getRawOriginal('status')));
        $this->assertSame(
            1,
            WebhookReceipt::query()
                ->where('provider', TypedValue::string(config('payment.driver', 'fake-payment')))
                ->where('event_id', $eventId)
                ->count()
        );
    }

    /**
     * Ensure captured payment is not downgraded by later webhook events.
     */
    public function test_payment_webhook_does_not_regress_captured_status(): void
    {
        $this->seed([RoleSeeder::class]);

        $user = User::factory()->create();
        $user->assignRole('customer');
        Sanctum::actingAs($user);

        $variant = $this->createActiveVariantWithInventory();

        $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $orderResponse = $this->withHeader('Idempotency-Key', 'payment-regression-order-key')
            ->postJson('/api/v1/checkout/place-order', [
                'email' => $user->email,
                'billing_address' => [
                    'line1' => '1 Main Street',
                    'city' => 'New York',
                    'country' => 'US',
                    'postcode' => '10001',
                ],
                'shipping_address' => [
                    'line1' => '1 Main Street',
                    'city' => 'New York',
                    'country' => 'US',
                    'postcode' => '10001',
                ],
            ])
            ->assertCreated();

        $orderId = $this->jsonString($orderResponse, 'data.id');

        $this->initiatePayment($orderId, 'payment-initiate-regression');

        $payment = Payment::query()
            ->whereHas('order', static fn ($query) => $query->where('id', $orderId))
            ->firstOrFail();

        $paidPayload = [
            'event_id' => 'evt-paid-regression-1',
            'transaction_id' => $payment->transaction_id,
            'status' => 'paid',
        ];

        $this->withHeader('X-Signature', hash('sha256', $paidPayload['event_id']))
            ->postJson('/api/v1/webhooks/payment', $paidPayload)
            ->assertAccepted();

        $failedPayload = [
            'event_id' => 'evt-paid-regression-2',
            'transaction_id' => $payment->transaction_id,
            'status' => 'failed',
        ];

        $this->withHeader('X-Signature', hash('sha256', $failedPayload['event_id']))
            ->postJson('/api/v1/webhooks/payment', $failedPayload)
            ->assertAccepted();

        $order = Order::query()->whereKey($orderId)->firstOrFail();
        $payment = $payment->fresh();

        $this->assertSame('paid', TypedValue::string($order->getRawOriginal('status')));
        $this->assertSame('captured', TypedValue::string($order->getRawOriginal('payment_status')));
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertSame('captured', TypedValue::string($payment->getRawOriginal('status')));
    }

    /**
     * Ensure webhook without transaction id is rejected.
     */
    public function test_payment_webhook_requires_transaction_id(): void
    {
        $payload = [
            'event_id' => 'evt-no-transaction',
            'status' => 'paid',
        ];

        $this->withHeader('X-Signature', hash('sha256', $payload['event_id']))
            ->postJson('/api/v1/webhooks/payment', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('error.message', 'Payment transaction id is required.');
    }

    /**
     * Ensure same event id with changed payload is rejected.
     */
    public function test_payment_webhook_rejects_payload_hash_mismatch_for_same_event_id(): void
    {
        $this->seed([RoleSeeder::class]);

        $user = User::factory()->create();
        $user->assignRole('customer');
        Sanctum::actingAs($user);

        $variant = $this->createActiveVariantWithInventory();

        $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $orderResponse = $this->withHeader('Idempotency-Key', 'payment-hash-order-key')
            ->postJson('/api/v1/checkout/place-order', [
                'email' => $user->email,
                'billing_address' => [
                    'line1' => '1 Main Street',
                    'city' => 'New York',
                    'country' => 'US',
                    'postcode' => '10001',
                ],
                'shipping_address' => [
                    'line1' => '1 Main Street',
                    'city' => 'New York',
                    'country' => 'US',
                    'postcode' => '10001',
                ],
            ])
            ->assertCreated();

        $orderId = $this->jsonString($orderResponse, 'data.id');
        $this->initiatePayment($orderId, 'payment-initiate-hash-mismatch');

        $payment = Payment::query()
            ->whereHas('order', static fn ($query) => $query->where('id', $orderId))
            ->firstOrFail();

        $eventId = 'evt-hash-mismatch';
        $firstPayload = [
            'event_id' => $eventId,
            'transaction_id' => $payment->transaction_id,
            'status' => 'paid',
        ];

        $this->withHeader('X-Signature', hash('sha256', $eventId))
            ->postJson('/api/v1/webhooks/payment', $firstPayload)
            ->assertAccepted();

        $secondPayload = [
            'event_id' => $eventId,
            'transaction_id' => $payment->transaction_id,
            'status' => 'failed',
        ];

        $this->withHeader('X-Signature', hash('sha256', $eventId))
            ->postJson('/api/v1/webhooks/payment', $secondPayload)
            ->assertUnprocessable()
            ->assertJsonPath('error.message', 'Webhook payload hash mismatch.');
    }

    /**
     * Create paid order and payment for webhook tests.
     *
     * @return array{Order, Payment}
     */
    private function createPaidOrderWithPayment(string $idempotencyKey): array
    {
        $this->seed([RoleSeeder::class]);

        $user = User::factory()->create();
        $user->assignRole('customer');
        Sanctum::actingAs($user);

        $variant = $this->createActiveVariantWithInventory();

        $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $orderResponse = $this->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/checkout/place-order', [
                'email' => $user->email,
                'billing_address' => [
                    'line1' => '1 Main Street',
                    'city' => 'New York',
                    'country' => 'US',
                    'postcode' => '10001',
                ],
                'shipping_address' => [
                    'line1' => '1 Main Street',
                    'city' => 'New York',
                    'country' => 'US',
                    'postcode' => '10001',
                ],
            ])
            ->assertCreated();

        $orderId = $this->jsonString($orderResponse, 'data.id');

        $this->initiatePayment($orderId, 'payment-initiate-'.$idempotencyKey);

        $order = Order::query()->whereKey($orderId)->firstOrFail();
        $payment = Payment::query()
            ->whereHas('order', static fn ($query) => $query->where('id', $orderId))
            ->firstOrFail();

        return [$order, $payment];
    }

    public function test_checkout_pay_requires_idempotency_key_header(): void
    {
        $this->seed([RoleSeeder::class]);

        $user = User::factory()->create();
        $user->assignRole('customer');
        Sanctum::actingAs($user);

        $variant = $this->createActiveVariantWithInventory();

        $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $orderResponse = $this->withHeader('Idempotency-Key', 'payment-missing-header-order-key')
            ->postJson('/api/v1/checkout/place-order', [
                'email' => $user->email,
                'billing_address' => [
                    'line1' => '1 Main Street',
                    'city' => 'New York',
                    'country' => 'US',
                    'postcode' => '10001',
                ],
                'shipping_address' => [
                    'line1' => '1 Main Street',
                    'city' => 'New York',
                    'country' => 'US',
                    'postcode' => '10001',
                ],
            ])
            ->assertCreated();

        $orderId = $this->jsonString($orderResponse, 'data.id');

        $this->withoutHeader('Idempotency-Key');

        $this->postJson('/api/v1/checkout/orders/'.$orderId.'/pay', [])
            ->assertBadRequest()
            ->assertJsonPath('error.message', 'Idempotency-Key header is required.')
            ->assertJsonPath('error.type', 'BadRequestHttpException')
            ->assertJsonPath('error.code', 'domain_failure');

        $this->assertSame(
            1,
            Payment::query()->whereHas('order', static fn ($query) => $query->where('id', $orderId))->count()
        );
    }

    private function initiatePayment(string $orderId, string $idempotencyKey): void
    {
        $this->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/checkout/orders/'.$orderId.'/pay', [])
            ->assertOk();
    }
}
