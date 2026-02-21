<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminOrderSummaryContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure admin orders list returns summary payload and detail endpoint returns full order.
     */
    public function test_admin_orders_list_is_summary_and_show_is_detail(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $order = Order::query()->create([
            'order_number' => 'ORD-SUMMARY-1001',
            'email' => 'summary@example.com',
            'status' => 'pending',
            'payment_status' => 'pending',
            'shipment_status' => 'pending',
            'currency' => 'USD',
            'subtotal' => 100,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => 100,
            'billing_address' => ['line1' => '1 Main Street', 'city' => 'New York', 'country' => 'US', 'postcode' => '10001'],
            'shipping_address' => ['line1' => '1 Main Street', 'city' => 'New York', 'country' => 'US', 'postcode' => '10001'],
            'cart_snapshot' => [],
            'placed_at' => now(),
        ]);

        $this->getJson('/api/v1/admin/orders')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'order_number',
                        'email',
                        'status',
                        'payment_status',
                        'shipment_status',
                        'currency',
                        'total',
                        'placed_at',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonMissingPath('data.0.items')
            ->assertJsonMissingPath('data.0.payments')
            ->assertJsonMissingPath('data.0.shipments');

        $this->getJson('/api/v1/admin/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_number',
                    'items',
                    'payments',
                    'shipments',
                ],
            ]);
    }
}
