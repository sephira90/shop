<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use App\Support\Data\TypedValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountOrdersApiTest extends TestCase
{
    use RefreshDatabase;

    private static int $orderSequence = 1000;

    /**
     * Ensure account orders endpoint applies query and status filters.
     */
    public function test_account_orders_applies_query_and_status_filters_on_canonical_summary_list(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $matching = $this->createOrder($user, [
            'order_number' => 'ORD-ALPHA-1',
            'status' => 'paid',
            'payment_status' => 'captured',
        ]);

        $this->createOrder($user, [
            'order_number' => 'ORD-ALPHA-2',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $this->createOrder($otherUser, [
            'order_number' => 'ORD-ALPHA-3',
            'status' => 'paid',
            'payment_status' => 'captured',
        ]);

        $response = $this->getJson('/api/v1/account/orders?q=alpha&status=paid&per_page=5')
            ->assertOk();

        $response->assertJsonPath('meta.per_page', 5);
        $response->assertJsonCount(1, 'data');
        $item = $this->jsonArray($response, 'data.0');
        $this->assertSame($matching->id, TypedValue::string($item['id']));
        $this->assertFalse(array_key_exists('items', $item));
        $this->assertFalse(array_key_exists('payments', $item));
        $this->assertFalse(array_key_exists('shipments', $item));
    }

    /**
     * Ensure account orders endpoint validates status filter.
     */
    public function test_my_orders_rejects_invalid_status_filter(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/account/orders?status=unknown')
            ->assertUnprocessable();
    }

    /**
     * Ensure account orders endpoints require authentication.
     */
    public function test_account_orders_require_authentication(): void
    {
        $this->getJson('/api/v1/account/orders')
            ->assertUnauthorized();
    }

    /**
     * Ensure legacy my orders alias remains backward-compatible.
     */
    public function test_legacy_my_orders_route_remains_backward_compatible(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $order = $this->createOrder($user);
        $this->createRelatedRecords($order);

        $response = $this->getJson('/api/v1/orders/me')
            ->assertOk();

        $response->assertJsonPath('data.0.id', $order->id);
        $response->assertJsonPath('data.0.subtotal', 100);
        $response->assertJsonPath('data.0.items.0.sku', 'SKU-101');
        $response->assertJsonPath('data.0.payments.0.gateway', 'fake');
        $response->assertJsonPath('data.0.shipments.0.provider', 'fake');
    }

    /**
     * Ensure account order detail returns owner-scoped detail payload.
     */
    public function test_account_order_detail_returns_owner_scoped_detail_payload(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $ownOrder = $this->createOrder($user, [
            'status' => 'paid',
            'payment_status' => 'captured',
            'shipment_status' => 'shipped',
        ]);
        $this->createRelatedRecords($ownOrder);

        $otherOrder = $this->createOrder($otherUser);
        $this->createRelatedRecords($otherOrder);

        $this->getJson('/api/v1/account/orders/'.$ownOrder->id)
            ->assertOk()
            ->assertJsonPath('data.id', $ownOrder->id)
            ->assertJsonPath('data.items.0.sku', 'SKU-101')
            ->assertJsonPath('data.payments.0.gateway', 'fake')
            ->assertJsonPath('data.shipments.0.provider', 'fake');

        $this->getJson('/api/v1/account/orders/'.$otherOrder->id)
            ->assertNotFound()
            ->assertJsonPath('error.type', 'NotFoundHttpException')
            ->assertJsonPath('error.code', 'not_found');
    }

    /**
     * Ensure account order summary returns global user metrics.
     */
    public function test_account_orders_summary_returns_aggregates_on_canonical_and_legacy_routes(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $this->createOrder($user, [
            'status' => 'paid',
            'payment_status' => 'captured',
            'shipment_status' => 'packed',
            'total' => 120.00,
        ]);

        $this->createOrder($user, [
            'status' => 'processing',
            'payment_status' => 'authorized',
            'shipment_status' => 'shipped',
            'total' => 50.00,
        ]);

        $this->createOrder($user, [
            'status' => 'pending',
            'payment_status' => 'pending',
            'shipment_status' => 'pending',
            'total' => 30.00,
        ]);

        $this->createOrder($otherUser, [
            'status' => 'paid',
            'payment_status' => 'captured',
            'shipment_status' => 'delivered',
            'total' => 999.00,
        ]);

        $expected = [
            'total_orders' => 3,
            'paid_orders' => 1,
            'in_delivery_orders' => 2,
            'total_spent' => 200,
        ];

        $this->getJson('/api/v1/account/orders/summary')
            ->assertOk()
            ->assertJsonPath('data.total_orders', $expected['total_orders'])
            ->assertJsonPath('data.paid_orders', $expected['paid_orders'])
            ->assertJsonPath('data.in_delivery_orders', $expected['in_delivery_orders'])
            ->assertJsonPath('data.total_spent', $expected['total_spent']);

        $this->getJson('/api/v1/orders/me/summary')
            ->assertOk()
            ->assertJsonPath('data.total_orders', 3)
            ->assertJsonPath('data.paid_orders', 1)
            ->assertJsonPath('data.in_delivery_orders', 2)
            ->assertJsonPath('data.total_spent', 200);
    }

    /**
     * Create a minimal order row for account API tests.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function createOrder(User $user, array $overrides = []): Order
    {
        self::$orderSequence++;

        $payload = [
            'order_number' => 'ORD-'.self::$orderSequence,
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => 'pending',
            'payment_status' => 'pending',
            'shipment_status' => 'pending',
            'currency' => 'USD',
            'subtotal' => 100.00,
            'discount_total' => 0.00,
            'shipping_total' => 0.00,
            'total' => 100.00,
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
        ];

        return Order::query()->create(array_merge($payload, $overrides));
    }

    /**
     * Create related item, payment, and shipment rows for account detail assertions.
     */
    private function createRelatedRecords(Order $order): void
    {
        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_variant_id' => null,
            'sku' => 'SKU-101',
            'name' => 'Example item',
            'quantity' => 2,
            'unit_price' => 50,
            'total_price' => 100,
            'meta' => [],
        ]);

        Payment::query()->create([
            'order_id' => $order->id,
            'idempotency_key' => 'pay-'.$order->id,
            'gateway' => 'fake',
            'transaction_id' => 'txn-'.$order->id,
            'amount' => 100,
            'currency' => 'USD',
            'status' => 'captured',
            'payload' => [],
        ]);

        Shipment::query()->create([
            'order_id' => $order->id,
            'provider' => 'fake',
            'tracking_number' => 'trk-'.$order->id,
            'status' => 'shipped',
            'cost' => 0,
            'payload' => [],
        ]);
    }
}
