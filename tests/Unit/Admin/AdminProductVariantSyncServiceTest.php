<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Application\Admin\Products\Dto\AdminProductVariantInputDto;
use App\Enums\ProductStatus;
use App\Models\Inventory;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Admin\ProductWrites\AdminProductVariantSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductVariantSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_updates_kept_variants_creates_new_ones_and_deletes_stale_variants(): void
    {
        $product = Product::query()->create([
            'sku' => 'PHONE-SYNC-1',
            'name' => 'Phone Sync',
            'slug' => 'phone-sync-1',
            'status' => ProductStatus::DRAFT->value,
        ]);

        /** @var ProductVariant $keptVariant */
        $keptVariant = $product->variants()->create([
            'sku' => 'PHONE-SYNC-1-BASE',
            'name' => 'Base',
            'attributes' => [],
            'price' => 100.00,
            'compare_at_price' => null,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        /** @var ProductVariant $staleVariant */
        $staleVariant = $product->variants()->create([
            'sku' => 'PHONE-SYNC-1-STALE',
            'name' => 'Stale',
            'attributes' => [],
            'price' => 120.00,
            'compare_at_price' => null,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $this->app->make(AdminProductVariantSyncService::class)->sync($product, [
            AdminProductVariantInputDto::fromValidated([
                'id' => $keptVariant->id,
                'sku' => 'PHONE-SYNC-1-BASE',
                'name' => 'Base Updated',
                'price' => 149.99,
                'compare_at_price' => 199.99,
                'currency' => 'USD',
                'is_active' => true,
                'inventory' => [
                    'quantity' => 12,
                    'reserved_quantity' => 4,
                    'low_stock_threshold' => 3,
                ],
            ]),
            AdminProductVariantInputDto::fromValidated([
                'sku' => 'PHONE-SYNC-1-PRO',
                'name' => 'Pro',
                'price' => 249.99,
                'currency' => 'USD',
                'is_active' => true,
                'inventory' => [
                    'quantity' => 6,
                    'reserved_quantity' => 1,
                    'low_stock_threshold' => 2,
                ],
            ]),
        ]);

        $this->assertDatabaseHas('product_variants', [
            'id' => $keptVariant->id,
            'name' => 'Base Updated',
            'price' => '149.99',
            'compare_at_price' => '199.99',
        ]);

        $this->assertDatabaseMissing('product_variants', [
            'id' => $staleVariant->id,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'PHONE-SYNC-1-PRO',
            'price' => '249.99',
        ]);

        $this->assertSame(2, Inventory::query()->count());
        $this->assertSame(2, Price::query()->count());
    }
}
