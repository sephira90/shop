<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ProductVariant;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestCheckoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure guest can place idempotent order with cart token.
     */
    public function test_guest_checkout_is_supported_and_idempotent(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $variant = ProductVariant::query()->firstOrFail();
        $guestToken = 'guest-checkout-token';

        $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'guest_token' => $guestToken,
        ])->assertOk();

        $payload = [
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
        ];

        $first = $this->withHeader('Idempotency-Key', 'guest-checkout-idempotency')
            ->postJson('/api/v1/checkout/place-order', $payload)
            ->assertCreated();

        $second = $this->withHeader('Idempotency-Key', 'guest-checkout-idempotency')
            ->postJson('/api/v1/checkout/place-order', $payload)
            ->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame($first->json('payment.payment_id'), $second->json('payment.payment_id'));
    }

    /**
     * Ensure guest token can be reused for new cart after checkout.
     */
    public function test_guest_token_is_reused_without_unique_errors_after_checkout(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $variant = ProductVariant::query()->firstOrFail();
        $guestToken = 'guest-cart-reuse-token';

        $firstCartResponse = $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'guest_token' => $guestToken,
        ])->assertOk();

        $firstCartId = $firstCartResponse->json('data.id');

        $this->withHeader('Idempotency-Key', 'guest-cart-reuse-checkout-key')
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
            ->assertCreated();

        $nextCartResponse = $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'guest_token' => $guestToken,
        ])->assertOk();

        $this->assertSame('active', $nextCartResponse->json('data.status'));
        $this->assertNotSame($firstCartId, $nextCartResponse->json('data.id'));
    }
}
