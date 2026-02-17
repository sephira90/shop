<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure payment webhook marks order as paid.
     */
    public function test_payment_webhook_processing(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $user = User::factory()->create();
        $user->assignRole('customer');
        Sanctum::actingAs($user);

        $variant = ProductVariant::query()->firstOrFail();

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

        $orderId = $orderResponse->json('data.id');

        $this->postJson('/api/v1/checkout/orders/'.$orderId.'/pay', [])
            ->assertOk();

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

        $order = Order::query()->findOrFail($orderId);
        $this->assertSame('paid', (string) $order->getRawOriginal('status'));
        $this->assertSame('captured', (string) $order->getRawOriginal('payment_status'));
    }

    /**
     * Ensure captured payment is not downgraded by later webhook events.
     */
    public function test_payment_webhook_does_not_regress_captured_status(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $user = User::factory()->create();
        $user->assignRole('customer');
        Sanctum::actingAs($user);

        $variant = ProductVariant::query()->firstOrFail();

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

        $orderId = $orderResponse->json('data.id');

        $this->postJson('/api/v1/checkout/orders/'.$orderId.'/pay', [])
            ->assertOk();

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

        $order = Order::query()->findOrFail($orderId);
        $payment = $payment->fresh();

        $this->assertSame('paid', (string) $order->getRawOriginal('status'));
        $this->assertSame('captured', (string) $order->getRawOriginal('payment_status'));
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertSame('captured', (string) $payment->getRawOriginal('status'));
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
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $user = User::factory()->create();
        $user->assignRole('customer');
        Sanctum::actingAs($user);

        $variant = ProductVariant::query()->firstOrFail();

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

        $orderId = $orderResponse->json('data.id');
        $this->postJson('/api/v1/checkout/orders/'.$orderId.'/pay', [])
            ->assertOk();

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
}
