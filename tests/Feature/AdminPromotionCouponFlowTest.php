<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Promotion;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesCatalogVariant;
use Tests\TestCase;

class AdminPromotionCouponFlowTest extends TestCase
{
    use CreatesCatalogVariant;
    use RefreshDatabase;

    /**
     * Ensure promotion code creates primary coupon and can be used at checkout.
     */
    public function test_promotion_code_creates_primary_coupon_for_checkout(): void
    {
        $this->seed([RoleSeeder::class]);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $variant = $this->createActiveVariantWithInventory();

        $promotionResponse = $this->postJson('/api/v1/admin/promotions', [
            'name' => 'Test Campaign',
            'code' => 'test1',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
            'starts_at' => now()->subHour()->toAtomString(),
            'ends_at' => now()->addDay()->toAtomString(),
        ])->assertCreated();

        $promotionId = $this->jsonInt($promotionResponse, 'data.id');

        $this->assertDatabaseHas('coupons', [
            'promotion_id' => $promotionId,
            'code' => 'TEST1',
            'is_active' => 1,
        ]);

        $guestToken = 'admin-promo-flow-guest';

        $this->postJson('/api/v1/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'guest_token' => $guestToken,
        ])->assertOk();

        $checkoutResponse = $this->withHeader('Idempotency-Key', 'admin-promo-flow-checkout')
            ->postJson('/api/v1/checkout/place-order', [
                'guest_token' => $guestToken,
                'coupon_code' => 'test1',
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

        $this->assertGreaterThan(0, $this->jsonFloat($checkoutResponse, 'data.discount_total'));
    }

    /**
     * Ensure manager can add and update coupon for existing promotion.
     */
    public function test_manager_can_add_and_update_coupon_for_promotion(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $promotion = Promotion::query()->create([
            'name' => 'No coupon campaign',
            'type' => 'fixed',
            'value' => 5,
            'is_active' => true,
        ]);

        $couponResponse = $this->postJson("/api/v1/admin/promotions/{$promotion->id}/coupons", [
            'code' => 'SECOND5',
            'is_active' => 'true',
            'max_redemptions' => 20,
            'expires_at' => now()->addDays(7)->toAtomString(),
        ])->assertCreated();

        $couponId = $this->jsonInt($couponResponse, 'data.id');

        $this->patchJson("/api/v1/admin/coupons/{$couponId}", [
            'is_active' => 'false',
            'max_redemptions' => 10,
            'expires_at' => null,
        ])->assertOk();

        $this->assertDatabaseHas('coupons', [
            'id' => $couponId,
            'promotion_id' => $promotion->id,
            'code' => 'SECOND5',
            'is_active' => 0,
            'max_redemptions' => 10,
        ]);

        $coupon = Coupon::query()->findOrFail($couponId);
        $this->assertNull($coupon->expires_at);
    }

    /**
     * Ensure promotion create accepts string boolean payloads for campaign and nested coupon flags.
     */
    public function test_manager_can_create_promotion_with_string_boolean_flags(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/v1/admin/promotions', [
            'name' => 'String Flag Campaign',
            'type' => 'fixed',
            'value' => 12,
            'is_active' => 'false',
            'coupon' => [
                'code' => 'STRING12',
                'is_active' => 'false',
                'max_redemptions' => 12,
            ],
        ])->assertCreated();

        $promotionId = $this->jsonInt($response, 'data.id');
        $couponId = $this->jsonInt($response, 'data.coupons.0.id');

        $this->assertDatabaseHas('promotions', [
            'id' => $promotionId,
            'is_active' => 0,
        ]);

        $this->assertDatabaseHas('coupons', [
            'id' => $couponId,
            'promotion_id' => $promotionId,
            'code' => 'STRING12',
            'is_active' => 0,
        ]);
    }

    /**
     * Ensure promotions index uses list contract with meta block.
     */
    public function test_promotions_index_returns_data_with_meta_contract(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $this->postJson('/api/v1/admin/promotions', [
            'name' => 'Contract check',
            'code' => 'CONTRACT1',
            'type' => 'percent',
            'value' => 8,
            'is_active' => true,
        ])->assertCreated();

        $this->getJson('/api/v1/admin/promotions')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'type',
                        'value',
                        'is_active',
                        'usage_limit',
                        'usage_count',
                        'starts_at',
                        'ends_at',
                        'coupons',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'total',
                ],
            ]);
    }

    /**
     * Ensure manager can update promotion campaign fields.
     */
    public function test_manager_can_update_promotion_campaign(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $promotionResponse = $this->postJson('/api/v1/admin/promotions', [
            'name' => 'Spring deal',
            'code' => 'SPRING10',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ])->assertCreated();

        $promotionId = $this->jsonInt($promotionResponse, 'data.id');

        $this->patchJson("/api/v1/admin/promotions/{$promotionId}", [
            'name' => 'Spring deal updated',
            'code' => 'SPRING20',
            'type' => 'fixed',
            'value' => 7.5,
            'is_active' => 'false',
            'starts_at' => null,
            'ends_at' => null,
            'usage_limit' => 100,
        ])->assertOk();

        $this->assertDatabaseHas('promotions', [
            'id' => $promotionId,
            'name' => 'Spring deal updated',
            'code' => 'SPRING20',
            'type' => 'fixed',
            'is_active' => 0,
            'usage_limit' => 100,
        ]);

        $this->assertDatabaseHas('coupons', [
            'promotion_id' => $promotionId,
            'code' => 'SPRING20',
        ]);
        $this->assertDatabaseMissing('coupons', [
            'promotion_id' => $promotionId,
            'code' => 'SPRING10',
        ]);
    }

    /**
     * Ensure manager can delete promotion and coupons are deleted by cascade.
     */
    public function test_manager_can_delete_promotion_campaign(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $promotionResponse = $this->postJson('/api/v1/admin/promotions', [
            'name' => 'Delete me',
            'code' => 'DELME',
            'type' => 'percent',
            'value' => 5,
            'is_active' => true,
        ])->assertCreated();

        $promotionId = $this->jsonInt($promotionResponse, 'data.id');
        $couponId = \App\Support\Data\TypedValue::int(Coupon::query()
            ->where('promotion_id', $promotionId)
            ->value('id'));

        $this->deleteJson("/api/v1/admin/promotions/{$promotionId}")
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->assertDatabaseMissing('promotions', [
            'id' => $promotionId,
        ]);
        $this->assertDatabaseMissing('coupons', [
            'id' => $couponId,
        ]);
    }
}
