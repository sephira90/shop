<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Shipping\ShippingWebhookIngressResolver;
use App\Services\Shipping\ShippingWebhookTransitionApplier;
use App\Services\Webhook\WebhookProcessingOutcome;
use App\Support\Data\JsonPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShippingWebhookTransitionApplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_delivered_status_updates_shipment_timestamps_and_completes_order(): void
    {
        $now = Carbon::parse('2026-02-28 15:00:00');
        Carbon::setTestNow($now);

        try {
            [$order, $shipment] = $this->createOrderWithShipment(
                orderStatus: OrderStatus::PAID,
                orderPaymentStatus: PaymentStatus::CAPTURED,
                orderShipmentStatus: ShipmentStatus::PACKED,
                shipmentStatus: ShipmentStatus::PACKED,
            );

            $resolvedPayload = app(ShippingWebhookIngressResolver::class)->resolve(JsonPayload::fromArray([
                'event_id' => 'evt-shipping-delivered',
                'tracking_number' => $shipment->tracking_number,
                'status' => 'delivered',
            ]));

            $outcome = DB::transaction(
                fn (): WebhookProcessingOutcome => app(ShippingWebhookTransitionApplier::class)->apply(
                    $resolvedPayload,
                    (string) config('shipping.driver', 'fake-shipping'),
                ),
            );

            $freshOrder = $order->fresh();
            $freshShipment = $shipment->fresh();

            $this->assertInstanceOf(Order::class, $freshOrder);
            $this->assertInstanceOf(Shipment::class, $freshShipment);
            $this->assertSame(WebhookProcessingOutcome::PROCESSED, $outcome);
            $this->assertSame(ShipmentStatus::DELIVERED, $freshShipment->status);
            $shipmentPayload = $freshShipment->getAttribute('payload');
            $this->assertIsArray($shipmentPayload);
            $this->assertSame('evt-shipping-delivered', $shipmentPayload['webhook']['event_id'] ?? null);
            $this->assertSame(
                $now->toDateTimeString(),
                Carbon::parse((string) $freshShipment->getRawOriginal('shipped_at'))->toDateTimeString(),
            );
            $this->assertSame(
                $now->toDateTimeString(),
                Carbon::parse((string) $freshShipment->getRawOriginal('delivered_at'))->toDateTimeString(),
            );
            $this->assertSame(OrderStatus::COMPLETED, $freshOrder->status);
            $this->assertSame(ShipmentStatus::DELIVERED, $freshOrder->shipment_status);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_apply_returns_duplicate_for_delivered_to_shipped_regression_and_keeps_state_stable(): void
    {
        $now = Carbon::parse('2026-02-28 15:10:00');
        Carbon::setTestNow($now);

        try {
            [$order, $shipment] = $this->createOrderWithShipment(
                orderStatus: OrderStatus::COMPLETED,
                orderPaymentStatus: PaymentStatus::CAPTURED,
                orderShipmentStatus: ShipmentStatus::DELIVERED,
                shipmentStatus: ShipmentStatus::DELIVERED,
                shippedAt: $now->copy()->subHour(),
                deliveredAt: $now->copy()->subMinutes(30),
            );

            $resolvedPayload = app(ShippingWebhookIngressResolver::class)->resolve(JsonPayload::fromArray([
                'event_id' => 'evt-shipping-regression',
                'tracking_number' => $shipment->tracking_number,
                'status' => 'shipped',
            ]));

            $outcome = DB::transaction(
                fn (): WebhookProcessingOutcome => app(ShippingWebhookTransitionApplier::class)->apply(
                    $resolvedPayload,
                    (string) config('shipping.driver', 'fake-shipping'),
                ),
            );

            $freshOrder = $order->fresh();
            $freshShipment = $shipment->fresh();

            $this->assertInstanceOf(Order::class, $freshOrder);
            $this->assertInstanceOf(Shipment::class, $freshShipment);
            $this->assertSame(WebhookProcessingOutcome::DUPLICATE, $outcome);
            $this->assertSame(ShipmentStatus::DELIVERED, $freshShipment->status);
            $this->assertSame(
                $now->copy()->subHour()->toDateTimeString(),
                Carbon::parse((string) $freshShipment->getRawOriginal('shipped_at'))->toDateTimeString(),
            );
            $this->assertSame(
                $now->copy()->subMinutes(30)->toDateTimeString(),
                Carbon::parse((string) $freshShipment->getRawOriginal('delivered_at'))->toDateTimeString(),
            );
            $this->assertSame(OrderStatus::COMPLETED, $freshOrder->status);
            $this->assertSame(ShipmentStatus::DELIVERED, $freshOrder->shipment_status);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * @return array{Order, Shipment}
     */
    private function createOrderWithShipment(
        OrderStatus $orderStatus,
        PaymentStatus $orderPaymentStatus,
        ShipmentStatus $orderShipmentStatus,
        ShipmentStatus $shipmentStatus,
        ?Carbon $shippedAt = null,
        ?Carbon $deliveredAt = null,
    ): array {
        $order = Order::query()->create([
            'order_number' => 'ORD-SHIPPING-WEBHOOK-TEST',
            'email' => 'guest@example.com',
            'status' => $orderStatus->value,
            'payment_status' => $orderPaymentStatus->value,
            'shipment_status' => $orderShipmentStatus->value,
            'currency' => 'USD',
            'subtotal' => 100,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100,
            'billing_address' => ['line1' => '1 Main Street'],
            'shipping_address' => ['line1' => '1 Main Street'],
            'cart_snapshot' => ['items' => []],
            'placed_at' => now(),
        ]);

        $shipment = Shipment::query()->create([
            'order_id' => $order->id,
            'provider' => (string) config('shipping.driver', 'fake-shipping'),
            'tracking_number' => 'trk-shipping-webhook-test-'.$shipmentStatus->value,
            'status' => $shipmentStatus->value,
            'cost' => 7.50,
            'payload' => ['provider' => 'fake-shipping'],
            'shipped_at' => $shippedAt,
            'delivered_at' => $deliveredAt,
        ]);

        return [$order, $shipment];
    }
}
