<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Application\Admin\Products\Dto\AdminProductVariantInputDto;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Admin\ProductWrites\AdminVariantInventorySyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminVariantInventorySyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_upserts_variant_inventory(): void
    {
        $variant = $this->createVariant('PHONE-INV-1');
        $service = $this->app->make(AdminVariantInventorySyncService::class);

        $service->sync($variant, $this->variantInput(7, 2, 3));
        $service->sync($variant, $this->variantInput(10, 4, 5));

        $this->assertDatabaseHas('inventories', [
            'product_variant_id' => $variant->id,
            'quantity' => 10,
            'reserved_quantity' => 4,
            'low_stock_threshold' => 5,
        ]);
    }

    private function createVariant(string $sku): ProductVariant
    {
        $product = Product::query()->create([
            'sku' => $sku,
            'name' => $sku,
            'slug' => strtolower($sku),
            'status' => ProductStatus::DRAFT->value,
        ]);

        /** @var ProductVariant $variant */
        $variant = $product->variants()->create([
            'sku' => $sku.'-BASE',
            'name' => 'Base',
            'attributes' => [],
            'price' => 199.99,
            'compare_at_price' => null,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        return $variant;
    }

    private function variantInput(int $quantity, int $reservedQuantity, int $lowStockThreshold): AdminProductVariantInputDto
    {
        return AdminProductVariantInputDto::fromValidated([
            'sku' => 'SYNC-INV',
            'name' => 'Sync Inventory',
            'price' => 199.99,
            'currency' => 'USD',
            'is_active' => true,
            'inventory' => [
                'quantity' => $quantity,
                'reserved_quantity' => $reservedQuantity,
                'low_stock_threshold' => $lowStockThreshold,
            ],
        ]);
    }
}
