<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Enums\ProductStatus;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Admin\ProductWrites\AdminVariantPriceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminVariantPriceSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_upserts_current_variant_price_snapshot(): void
    {
        $variant = $this->createVariant('PHONE-PRICE-1');
        $service = $this->app->make(AdminVariantPriceSyncService::class);

        $service->sync($variant);

        $variant->update(['price' => 149.99]);
        $variant->refresh();
        $service->sync($variant);

        $this->assertSame(1, Price::query()->count());
        $this->assertDatabaseHas('prices', [
            'product_variant_id' => $variant->id,
            'currency' => 'USD',
            'amount' => '149.99',
            'starts_at' => null,
            'ends_at' => null,
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
}
