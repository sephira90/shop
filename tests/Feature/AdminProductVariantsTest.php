<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        $productId = $this->jsonInt($response, 'data.id');
        $variantId = $this->jsonInt($response, 'data.variants.0.id');

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

        $productId = $this->jsonInt($createResponse, 'data.id');
        $baseVariantId = $this->jsonInt($createResponse, 'data.variants.0.id');

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

    /**
     * Ensure API emits object attributes shape for variants without explicit attributes.
     */
    public function test_product_variant_without_attributes_is_returned_as_empty_object(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/v1/admin/products', [
            'sku' => 'PHONE-003',
            'name' => 'Phone 3',
            'status' => 'draft',
            'variants' => [
                [
                    'sku' => 'PHONE-003-BASE',
                    'name' => 'Base',
                    'price' => 599.99,
                    'currency' => 'USD',
                    'is_active' => true,
                    'inventory' => [
                        'quantity' => 10,
                        'reserved_quantity' => 1,
                        'low_stock_threshold' => 2,
                    ],
                ],
            ],
        ])->assertCreated();

        $this->assertStringContainsString('"attributes":{}', (string) $response->getContent());
    }

    /**
     * Ensure product requests accept string boolean payloads for top-level and wildcard variant flags.
     */
    public function test_manager_can_create_and_update_product_with_string_boolean_flags(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $createResponse = $this->postJson('/api/v1/admin/products', [
            'sku' => 'PHONE-BOOL-001',
            'name' => 'Boolean Phone',
            'status' => 'draft',
            'is_featured' => 'true',
            'variants' => [
                [
                    'sku' => 'PHONE-BOOL-001-BASE',
                    'name' => 'Base',
                    'price' => 499.99,
                    'currency' => 'USD',
                    'is_active' => 'false',
                    'inventory' => [
                        'quantity' => 4,
                        'reserved_quantity' => 0,
                        'low_stock_threshold' => 1,
                    ],
                ],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.is_featured', true)
            ->assertJsonPath('data.variants.0.is_active', false);

        $productId = $this->jsonInt($createResponse, 'data.id');
        $variantId = $this->jsonInt($createResponse, 'data.variants.0.id');

        $this->putJson('/api/v1/admin/products/'.$productId, [
            'sku' => 'PHONE-BOOL-001',
            'name' => 'Boolean Phone Updated',
            'status' => 'active',
            'is_featured' => 'false',
            'variants' => [
                [
                    'id' => $variantId,
                    'sku' => 'PHONE-BOOL-001-BASE',
                    'name' => 'Base Updated',
                    'price' => 459.99,
                    'currency' => 'USD',
                    'is_active' => 'true',
                    'inventory' => [
                        'quantity' => 7,
                        'reserved_quantity' => 1,
                        'low_stock_threshold' => 2,
                    ],
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.is_featured', false)
            ->assertJsonPath('data.variants.0.is_active', true);

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'is_featured' => 0,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variantId,
            'is_active' => 1,
        ]);
    }

    /**
     * Ensure admin product list and detail read paths return variants in deterministic id order.
     */
    public function test_admin_product_read_paths_order_variants_by_id(): void
    {
        $this->seed(RoleSeeder::class);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $product = Product::query()->create([
            'sku' => 'PHONE-ORDER-001',
            'name' => 'Ordered Phone',
            'slug' => 'ordered-phone',
            'status' => 'draft',
        ]);

        DB::table('product_variants')->insert([
            [
                'id' => 30,
                'product_id' => $product->id,
                'sku' => 'PHONE-ORDER-001-30',
                'name' => 'Variant 30',
                'attributes' => json_encode(['position' => 30], JSON_THROW_ON_ERROR),
                'price' => 300.00,
                'compare_at_price' => null,
                'currency' => 'USD',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 10,
                'product_id' => $product->id,
                'sku' => 'PHONE-ORDER-001-10',
                'name' => 'Variant 10',
                'attributes' => json_encode(['position' => 10], JSON_THROW_ON_ERROR),
                'price' => 100.00,
                'compare_at_price' => null,
                'currency' => 'USD',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 20,
                'product_id' => $product->id,
                'sku' => 'PHONE-ORDER-001-20',
                'name' => 'Variant 20',
                'attributes' => json_encode(['position' => 20], JSON_THROW_ON_ERROR),
                'price' => 200.00,
                'compare_at_price' => null,
                'currency' => 'USD',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->getJson('/api/v1/admin/products?per_page=10')
            ->assertOk()
            ->assertJsonPath('data.0.id', $product->id)
            ->assertJsonPath('data.0.variants.0.id', 10)
            ->assertJsonPath('data.0.variants.1.id', 20)
            ->assertJsonPath('data.0.variants.2.id', 30);

        $this->getJson('/api/v1/admin/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.variants.0.id', 10)
            ->assertJsonPath('data.variants.1.id', 20)
            ->assertJsonPath('data.variants.2.id', 30);
    }
}
