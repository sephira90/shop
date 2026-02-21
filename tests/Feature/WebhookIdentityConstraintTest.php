<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class WebhookIdentityConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_transaction_id_is_unique_per_gateway(): void
    {
        $orderA = $this->createOrder('payment-a');
        $orderB = $this->createOrder('payment-b');

        $this->createPayment($orderA, 'idem-payment-a', 'fake-payment', 'txn-identity-1');

        $this->expectException(QueryException::class);

        $this->createPayment($orderB, 'idem-payment-b', 'fake-payment', 'txn-identity-1');
    }

    public function test_payment_transaction_id_can_repeat_across_gateways(): void
    {
        $orderA = $this->createOrder('payment-cross-a');
        $orderB = $this->createOrder('payment-cross-b');

        $this->createPayment($orderA, 'idem-payment-cross-a', 'fake-payment', 'txn-identity-2');
        $this->createPayment($orderB, 'idem-payment-cross-b', 'other-payment', 'txn-identity-2');

        $this->assertDatabaseCount('payments', 2);
    }

    public function test_shipment_tracking_number_is_unique_per_provider(): void
    {
        $orderA = $this->createOrder('shipping-a');
        $orderB = $this->createOrder('shipping-b');

        $this->createShipment($orderA, 'fake-shipping', 'trk-identity-1');

        $this->expectException(QueryException::class);

        $this->createShipment($orderB, 'fake-shipping', 'trk-identity-1');
    }

    public function test_shipment_tracking_number_can_repeat_across_providers(): void
    {
        $orderA = $this->createOrder('shipping-cross-a');
        $orderB = $this->createOrder('shipping-cross-b');

        $this->createShipment($orderA, 'fake-shipping', 'trk-identity-2');
        $this->createShipment($orderB, 'other-shipping', 'trk-identity-2');

        $this->assertDatabaseCount('shipments', 2);
    }

    private function createOrder(string $suffix): Order
    {
        return Order::query()->create([
            'order_number' => 'ORD-'.$suffix.'-'.Str::upper(Str::random(6)),
            'email' => $suffix.'@example.test',
            'status' => 'pending',
            'payment_status' => 'pending',
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

    private function createPayment(Order $order, string $idempotencyKey, string $gateway, string $transactionId): Payment
    {
        return Payment::query()->create([
            'order_id' => $order->id,
            'idempotency_key' => $idempotencyKey,
            'gateway' => $gateway,
            'transaction_id' => $transactionId,
            'amount' => 100,
            'currency' => 'USD',
            'status' => 'pending',
            'payload' => [],
        ]);
    }

    private function createShipment(Order $order, string $provider, string $trackingNumber): Shipment
    {
        return Shipment::query()->create([
            'order_id' => $order->id,
            'provider' => $provider,
            'tracking_number' => $trackingNumber,
            'status' => 'pending',
            'cost' => 0,
            'payload' => [],
        ]);
    }
}
