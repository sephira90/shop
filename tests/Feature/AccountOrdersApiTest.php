<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountOrdersApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure account orders endpoint applies query and status filters.
     */
    public function test_my_orders_applies_query_and_status_filters(): void
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

        $response = $this->getJson('/api/v1/orders/me?q=alpha&status=paid&per_page=5')
            ->assertOk();

        $response->assertJsonPath('meta.per_page', 5);
        $response->assertJsonCount(1, 'data');
        $this->assertSame($matching->id, (string) $response->json('data.0.id'));
    }

    /**
     * Ensure account orders endpoint validates status filter.
     */
    public function test_my_orders_rejects_invalid_status_filter(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/orders/me?status=unknown')
            ->assertUnprocessable();
    }

    /**
     * Ensure account order summary returns global user metrics.
     */
    public function test_my_orders_summary_returns_aggregates(): void
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
        static $sequence = 1000;
        $sequence++;

        $payload = [
            'order_number' => 'ORD-'.$sequence,
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
}
