<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Application\Admin\Products\Dto\AdminProductVariantInputDto;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Admin\ProductWrites\AdminProductVariantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminProductVariantResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_returns_existing_variant_when_same_product_reuses_sku(): void
    {
        $product = $this->createProduct('PHONE-RESOLVE-1', 'phone-resolve-1');
        $variant = $product->variants()->create($this->variantAttributes('PHONE-RESOLVE-1-BLK', 'Black'));

        $resolved = $this->app->make(AdminProductVariantResolver::class)->resolve(
            $product,
            AdminProductVariantInputDto::fromValidated([
                'sku' => 'PHONE-RESOLVE-1-BLK',
                'name' => 'Black',
                'price' => 199.99,
                'currency' => 'USD',
                'is_active' => true,
                'inventory' => ['quantity' => 5, 'reserved_quantity' => 1, 'low_stock_threshold' => 2],
            ]),
            0,
        );

        $this->assertInstanceOf(ProductVariant::class, $resolved);
        $this->assertSame($variant->getKey(), $resolved->getKey());
    }

    public function test_resolve_throws_when_sku_belongs_to_another_product(): void
    {
        $product = $this->createProduct('PHONE-RESOLVE-2', 'phone-resolve-2');
        $otherProduct = $this->createProduct('PHONE-RESOLVE-3', 'phone-resolve-3');
        $otherProduct->variants()->create($this->variantAttributes('PHONE-COLLISION', 'Collision'));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Variant SKU already exists.');

        $this->app->make(AdminProductVariantResolver::class)->resolve(
            $product,
            AdminProductVariantInputDto::fromValidated([
                'sku' => 'PHONE-COLLISION',
                'name' => 'Collision',
                'price' => 299.99,
                'currency' => 'USD',
                'is_active' => true,
                'inventory' => ['quantity' => 5, 'reserved_quantity' => 0, 'low_stock_threshold' => 2],
            ]),
            1,
        );
    }

    private function createProduct(string $sku, string $slug): Product
    {
        return Product::query()->create([
            'sku' => $sku,
            'name' => $sku,
            'slug' => $slug,
            'status' => ProductStatus::DRAFT->value,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function variantAttributes(string $sku, string $name): array
    {
        return [
            'sku' => $sku,
            'name' => $name,
            'attributes' => ['color' => strtolower($name)],
            'price' => 99.99,
            'compare_at_price' => null,
            'currency' => 'USD',
            'is_active' => true,
        ];
    }
}
