<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminProductVariantsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure manager can create product with variants and inventory.
     */
    public function test_manager_can_create_product_with_variants(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/v1/admin/products', [
            'sku' => 'PHONE-001',
            'name' => 'Phone',
            'status' => 'draft',
            'variants' => [
                [
                    'sku' => 'PHONE-001-128-BLK',
                    'name' => '128GB Black',
                    'price' => 799.99,
                    'currency' => 'USD',
                    'is_active' => true,
                    'attributes' => [
                        'storage' => '128GB',
                        'color' => 'black',
                    ],
                    'inventory' => [
                        'quantity' => 15,
                        'reserved_quantity' => 2,
                        'low_stock_threshold' => 4,
                    ],
                ],
            ],
        ])->assertCreated();

        $productId = (int) $response->json('data.id');
        $variantId = (int) $response->json('data.variants.0.id');

        $this->assertDatabaseHas('product_variants', [
            'id' => $variantId,
            'product_id' => $productId,
            'sku' => 'PHONE-001-128-BLK',
            'price' => '799.99',
            'currency' => 'USD',
        ]);

        $this->assertDatabaseHas('inventories', [
            'product_variant_id' => $variantId,
            'quantity' => 15,
            'reserved_quantity' => 2,
            'low_stock_threshold' => 4,
        ]);

        $this->assertDatabaseHas('prices', [
            'product_variant_id' => $variantId,
            'amount' => '799.99',
            'currency' => 'USD',
            'starts_at' => null,
            'ends_at' => null,
        ]);
    }

    /**
     * Ensure manager can update variants and stale variant is deleted.
     */
    public function test_manager_can_update_product_variants(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $createResponse = $this->postJson('/api/v1/admin/products', [
            'sku' => 'PHONE-002',
            'name' => 'Phone 2',
            'status' => 'draft',
            'variants' => [
                [
                    'sku' => 'PHONE-002-128-BLK',
                    'name' => '128GB Black',
                    'price' => 699.99,
                    'currency' => 'USD',
                    'is_active' => true,
                    'attributes' => ['storage' => '128GB'],
                    'inventory' => [
                        'quantity' => 8,
                        'reserved_quantity' => 1,
                        'low_stock_threshold' => 3,
                    ],
                ],
                [
                    'sku' => 'PHONE-002-256-BLK',
                    'name' => '256GB Black',
                    'price' => 799.99,
                    'currency' => 'USD',
                    'is_active' => true,
                    'attributes' => ['storage' => '256GB'],
                    'inventory' => [
                        'quantity' => 5,
                        'reserved_quantity' => 0,
                        'low_stock_threshold' => 2,
                    ],
                ],
            ],
        ])->assertCreated();

        $productId = (int) $createResponse->json('data.id');
        $baseVariantId = (int) $createResponse->json('data.variants.0.id');

        $this->putJson('/api/v1/admin/products/'.$productId, [
            'sku' => 'PHONE-002',
            'name' => 'Phone 2 Updated',
            'status' => 'active',
            'variants' => [
                [
                    'id' => $baseVariantId,
                    'sku' => 'PHONE-002-128-BLK',
                    'name' => '128GB Black Updated',
                    'price' => 649.99,
                    'compare_at_price' => 699.99,
                    'currency' => 'USD',
                    'is_active' => true,
                    'attributes' => ['storage' => '128GB', 'edition' => '2026'],
                    'inventory' => [
                        'quantity' => 12,
                        'reserved_quantity' => 20,
                        'low_stock_threshold' => 4,
                    ],
                ],
                [
                    'sku' => 'PHONE-002-512-BLK',
                    'name' => '512GB Black',
                    'price' => 999.99,
                    'currency' => 'USD',
                    'is_active' => true,
                    'attributes' => ['storage' => '512GB'],
                    'inventory' => [
                        'quantity' => 3,
                        'reserved_quantity' => 0,
                        'low_stock_threshold' => 1,
                    ],
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.name', 'Phone 2 Updated')
            ->assertJsonCount(2, 'data.variants');

        $this->assertDatabaseHas('product_variants', [
            'id' => $baseVariantId,
            'product_id' => $productId,
            'name' => '128GB Black Updated',
            'price' => '649.99',
            'compare_at_price' => '699.99',
        ]);

        $this->assertDatabaseHas('inventories', [
            'product_variant_id' => $baseVariantId,
            'quantity' => 12,
            'reserved_quantity' => 12,
            'low_stock_threshold' => 4,
        ]);

        $this->assertDatabaseMissing('product_variants', [
            'product_id' => $productId,
            'sku' => 'PHONE-002-256-BLK',
        ]);

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $productId,
            'sku' => 'PHONE-002-512-BLK',
            'price' => '999.99',
        ]);
    }
}
