<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Product;
use App\Models\ProductVariant;
use Database\Factories\InventoryFactory;
use Database\Factories\ProductFactory;
use Database\Factories\ProductVariantFactory;

trait CreatesCatalogVariant
{
    /**
     * @param  list<float>  $prices
     */
    protected function createActiveProductWithVariants(array $prices): Product
    {
        $product = ProductFactory::new()->createOne();

        foreach ($prices as $index => $price) {
            ProductVariantFactory::new()->createOne([
                'product_id' => $product->id,
                'price' => $price,
                'compare_at_price' => round($price + 10, 2),
                'currency' => 'USD',
                'is_active' => true,
                'attributes' => ['position' => $index + 1],
            ]);
        }

        return $product->fresh(['variants']) ?? $product;
    }

    protected function createActiveVariantWithInventory(int $quantity = 100, float $price = 99.99): ProductVariant
    {
        $product = $this->createActiveProductWithVariants([$price]);
        $variant = $product->variants->first();

        if (! $variant instanceof ProductVariant) {
            $variant = ProductVariantFactory::new()->createOne([
                'product_id' => $product->id,
                'price' => $price,
                'compare_at_price' => round($price + 10, 2),
                'currency' => 'USD',
                'is_active' => true,
            ]);
        }

        InventoryFactory::new()->createOne([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
            'reserved_quantity' => 0,
        ]);

        return $variant;
    }
}
