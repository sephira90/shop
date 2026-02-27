<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\User;
use App\Models\WebhookReceipt;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShippingWebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure webhook without signature header is rejected.
     */
    public function test_shipping_webhook_requires_signature_header(): void
    {
        $this->postJson('/api/v1/webhooks/shipping', [
            'event_id' => 'evt-shipping-missing-signature',
            'tracking_number' => 'trk-missing-signature',
            'status' => 'shipped',
        ])
            ->assertBadRequest()
            ->assertJsonPath('error.message', 'Missing X-Signature header.');
    }

    /**
     * Ensure delivered shipment status cannot be regressed by late events.
     */
    public function test_shipping_webhook_does_not_regress_delivered_status(): void
    {
        [$order, $shipment] = $this->createPaidOrderWithShipment();

        $deliveredPayload = [
            'event_id' => 'evt-shipping-delivered-1',
            'tracking_number' => $shipment->tracking_number,
            'status' => 'delivered',
        ];

        $this->withHeader('X-Signature', hash('sha256', $deliveredPayload['event_id']))
            ->postJson('/api/v1/webhooks/shipping', $deliveredPayload)
            ->assertOk();

        $lateShippedPayload = [
            'event_id' => 'evt-shipping-delivered-2',
            'tracking_number' => $shipment->tracking_number,
            'status' => 'shipped',
        ];

        $this->withHeader('X-Signature', hash('sha256', $lateShippedPayload['event_id']))
            ->postJson('/api/v1/webhooks/shipping', $lateShippedPayload)
            ->assertOk();

        $shipment = $shipment->fresh();
        $order = $order->fresh();

        $this->assertInstanceOf(Shipment::class, $shipment);
        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame('delivered', (string) $shipment->getRawOriginal('status'));
        $this->assertSame('completed', (string) $order->getRawOriginal('status'));
        $this->assertSame('delivered', (string) $order->getRawOriginal('shipment_status'));
    }

    /**
     * Ensure replay of same shipping event payload is idempotent.
     */
    public function test_shipping_webhook_replay_with_same_event_id_is_idempotent(): void
    {
        [$order, $shipment] = $this->createPaidOrderWithShipment();

        $eventId = 'evt-shipping-replay';
        $payload = [
            'event_id' => $eventId,
            'tracking_number' => $shipment->tracking_number,
            'status' => 'shipped',
        ];

        $this->withHeader('X-Signature', hash('sha256', $eventId))
            ->postJson('/api/v1/webhooks/shipping', $payload)
            ->assertOk();

        $this->withHeader('X-Signature', hash('sha256', $eventId))
            ->postJson('/api/v1/webhooks/shipping', $payload)
            ->assertOk();

        $shipment = $shipment->fresh();
        $order = $order->fresh();

        $this->assertInstanceOf(Shipment::class, $shipment);
        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame('shipped', (string) $shipment->getRawOriginal('status'));
        $this->assertSame('paid', (string) $order->getRawOriginal('status'));
        $this->assertSame('shipped', (string) $order->getRawOriginal('shipment_status'));
        $this->assertSame(
            1,
            WebhookReceipt::query()
                ->where('provider', (string) config('shipping.driver', 'fake-shipping'))
                ->where('event_id', $eventId)
                ->count()
        );
    }

    /**
     * Ensure same shipping event id with changed payload is rejected.
     */
    public function test_shipping_webhook_rejects_payload_hash_mismatch_for_same_event_id(): void
    {
        [, $shipment] = $this->createPaidOrderWithShipment();

        $eventId = 'evt-shipping-hash';
        $firstPayload = [
            'event_id' => $eventId,
            'tracking_number' => $shipment->tracking_number,
            'status' => 'shipped',
        ];

        $this->withHeader('X-Signature', hash('sha256', $eventId))
            ->postJson('/api/v1/webhooks/shipping', $firstPayload)
            ->assertOk();

        $secondPayload = [
            'event_id' => $eventId,
            'tracking_number' => $shipment->tracking_number,
            'status' => 'delivered',
        ];

        $this->withHeader('X-Signature', hash('sha256', $eventId))
            ->postJson('/api/v1/webhooks/shipping', $secondPayload)
            ->assertUnprocessable()
            ->assertJsonPath('error.message', 'Webhook payload hash mismatch.');
    }

    /**
     * Create paid order and shipment for webhook tests.
     *
     * @return array{Order, Shipment}
     */
    private function createPaidOrderWithShipment(): array
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

        $orderResponse = $this->withHeader('Idempotency-Key', 'shipping-order-key')
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

        $paymentPayload = [
            'event_id' => 'evt-shipping-payment-captured',
            'transaction_id' => $payment->transaction_id,
            'status' => 'paid',
        ];

        $this->withHeader('X-Signature', hash('sha256', $paymentPayload['event_id']))
            ->postJson('/api/v1/webhooks/payment', $paymentPayload)
            ->assertAccepted();

        $order = Order::query()->findOrFail($orderId);
        $shipment = Shipment::query()->where('order_id', $orderId)->firstOrFail();

        return [$order, $shipment];
    }
}
