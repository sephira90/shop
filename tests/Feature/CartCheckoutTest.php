<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartCheckoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure cart to checkout flow is idempotent.
     */
    public function test_checkout_is_idempotent(): void
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

        $payload = [
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
        ];

        $idempotencyKey = 'checkout-test-key';

        $first = $this->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/checkout/place-order', $payload)
            ->assertCreated();

        $second = $this->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/checkout/place-order', $payload)
            ->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
    }

    /**
     * Ensure same cart cannot be checked out twice using another key.
     */
    public function test_second_checkout_with_another_idempotency_key_is_rejected(): void
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

        $payload = [
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
        ];

        $this->withHeader('Idempotency-Key', 'checkout-first-key')
            ->postJson('/api/v1/checkout/place-order', $payload)
            ->assertCreated();

        $this->withHeader('Idempotency-Key', 'checkout-second-key')
            ->postJson('/api/v1/checkout/place-order', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('error.message', 'Cart is not active for checkout.');

        $this->assertSame(1, Order::query()->count());
    }

    /**
     * Ensure idempotency key cannot be reused with different payload.
     */
    public function test_same_idempotency_key_with_different_payload_is_rejected(): void
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

        $this->withHeader('Idempotency-Key', 'checkout-payload-key')
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

        $this->withHeader('Idempotency-Key', 'checkout-payload-key')
            ->postJson('/api/v1/checkout/place-order', [
                'email' => 'another@example.com',
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
            ->assertUnprocessable()
            ->assertJsonPath('error.message', 'Idempotency key reused with different payload.');
    }

    /**
     * Ensure checkout rejects cart when inventory changed after add-to-cart.
     */
    public function test_checkout_rejects_when_inventory_becomes_insufficient(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $variant = ProductVariant::query()->with('inventory')->firstOrFail();
        $guestToken = 'checkout-insufficient-stock-token';

        $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'guest_token' => $guestToken,
        ])->assertOk();

        $variant->inventory?->update([
            'quantity' => 1,
            'reserved_quantity' => 0,
        ]);

        $this->withHeader('Idempotency-Key', 'checkout-insufficient-stock')
            ->postJson('/api/v1/checkout/place-order', [
                'guest_token' => $guestToken,
                'email' => 'guest@example.com',
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
            ->assertUnprocessable()
            ->assertJsonPath('error.message', 'Insufficient stock during checkout.');

        $this->assertSame(0, Order::query()->count());
    }
}
