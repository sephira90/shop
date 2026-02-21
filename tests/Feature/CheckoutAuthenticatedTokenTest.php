<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutAuthenticatedTokenTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure authenticated user can place order without guest token.
     */
    public function test_authenticated_user_can_checkout_without_guest_token(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole('admin');

        $variant = ProductVariant::query()->firstOrFail();
        $cart = Cart::query()->create([
            'user_id' => $user->id,
            'currency' => 'USD',
            'status' => CartStatus::ACTIVE->value,
        ]);

        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => $variant->price,
            'line_total' => $variant->price,
        ]);

        $token = $user->createToken('checkout-test-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Idempotency-Key', 'checkout-authenticated-token')
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
            ->assertCreated()
            ->assertJsonPath('data.email', $user->email);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    /**
     * Ensure cart endpoints use Sanctum user even with guest token payload.
     */
    public function test_authenticated_cart_item_is_bound_to_user_cart(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole('admin');

        $variant = ProductVariant::query()->firstOrFail();
        $token = $user->createToken('cart-auth-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity' => 1,
                'guest_token' => 'legacy-guest-token',
            ])
            ->assertOk()
            ->assertJsonPath('data.guest_token', null);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'status' => CartStatus::ACTIVE->value,
        ]);

        $this->assertDatabaseMissing('carts', [
            'guest_token' => 'legacy-guest-token',
            'status' => CartStatus::ACTIVE->value,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Idempotency-Key', 'checkout-auth-cart-token')
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
    }

    /**
     * Ensure authenticated checkout merges guest cart by provided token.
     */
    public function test_authenticated_checkout_merges_guest_cart(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole('admin');

        $variant = ProductVariant::query()->firstOrFail();
        $guestCart = Cart::query()->create([
            'guest_token' => 'checkout-merge-token',
            'currency' => 'USD',
            'status' => CartStatus::ACTIVE->value,
        ]);

        CartItem::query()->create([
            'cart_id' => $guestCart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => $variant->price,
            'line_total' => $variant->price,
        ]);

        $token = $user->createToken('checkout-merge-auth-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Idempotency-Key', 'checkout-auth-merge-token')
            ->postJson('/api/v1/checkout/place-order', [
                'guest_token' => 'checkout-merge-token',
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

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        $this->assertDatabaseHas('carts', [
            'id' => $guestCart->id,
            'status' => CartStatus::ABANDONED->value,
        ]);
    }
}
