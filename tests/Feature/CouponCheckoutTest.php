<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PromotionType;
use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Models\Promotion;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponCheckoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure coupon discount is applied during checkout.
     */
    public function test_coupon_discount_is_applied_to_order_total(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $variant = ProductVariant::query()->firstOrFail();
        $guestToken = 'coupon-checkout-token';

        $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'guest_token' => $guestToken,
        ])->assertOk();

        $promotion = Promotion::query()->create([
            'name' => 'Save ten percent',
            'code' => 'PROMO10',
            'type' => PromotionType::PERCENT->value,
            'value' => 10,
            'is_active' => true,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);

        $coupon = Coupon::query()->create([
            'promotion_id' => $promotion->id,
            'code' => 'SAVE10',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Idempotency-Key', 'coupon-checkout-idempotency')
            ->postJson('/api/v1/checkout/place-order', [
                'guest_token' => $guestToken,
                'coupon_code' => 'save10',
                'email' => 'guest@example.com',
                'billing_address' => [
                    'line1' => '2 Main Street',
                    'city' => 'New York',
                    'country' => 'US',
                    'postcode' => '10001',
                ],
                'shipping_address' => [
                    'line1' => '2 Main Street',
                    'city' => 'New York',
                    'country' => 'US',
                    'postcode' => '10001',
                ],
            ])
            ->assertCreated();

        $subtotal = $this->jsonFloat($response, 'data.subtotal');
        $discount = $this->jsonFloat($response, 'data.discount_total');
        $total = $this->jsonFloat($response, 'data.total');

        $this->assertSame(round($subtotal * 0.1, 2), $discount);
        $this->assertSame(round($subtotal - $discount, 2), $total);
        $this->assertSame(1, $coupon->fresh()?->redeemed_count);
        $this->assertSame(1, $promotion->fresh()?->usage_count);
    }
}
