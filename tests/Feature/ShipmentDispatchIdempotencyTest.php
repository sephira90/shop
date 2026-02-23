<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\DispatchShipmentJob;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Shipping\ShippingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ShipmentDispatchIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipping_service_create_shipment_is_idempotent_for_same_order(): void
    {
        $order = $this->createCapturedOrder('service-idempotent');

        /** @var ShippingService $shippingService */
        $shippingService = app(ShippingService::class);

        $firstShipment = $shippingService->createShipment($order);
        $secondShipment = $shippingService->createShipment($order->fresh() ?? $order);

        $this->assertSame($firstShipment->id, $secondShipment->id);
        $this->assertDatabaseCount('shipments', 1);
    }

    public function test_dispatch_shipment_job_retry_does_not_create_duplicate_shipments(): void
    {
        $order = $this->createCapturedOrder('job-retry');

        DispatchShipmentJob::dispatchSync($order->id);
        DispatchShipmentJob::dispatchSync($order->id);

        $shipments = Shipment::query()
            ->where('order_id', $order->id)
            ->get();

        $this->assertCount(1, $shipments);
    }

    private function createCapturedOrder(string $suffix): Order
    {
        return Order::query()->create([
            'order_number' => 'ORD-shipment-'.$suffix.'-'.Str::upper(Str::random(6)),
            'email' => $suffix.'@example.test',
            'status' => 'paid',
            'payment_status' => 'captured',
            'shipment_status' => 'pending',
            'currency' => 'USD',
            'subtotal' => 100,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100,
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
            'cart_snapshot' => [],
            'placed_at' => now(),
        ]);
    }
}
