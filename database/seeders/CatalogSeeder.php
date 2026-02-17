<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * Seed demo catalog data.
     */
    public function run(): void
    {
        $categories = collect([
            ['name' => 'Electronics', 'slug' => 'electronics'],
            ['name' => 'Apparel', 'slug' => 'apparel'],
            ['name' => 'Home', 'slug' => 'home'],
        ])->map(static function (array $row): Category {
            return Category::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'description' => $row['name'].' category',
                    'is_active' => true,
                ],
            );
        });

        foreach (range(1, 18) as $index) {
            $category = $categories->random();
            $sku = 'SKU-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT);

            $product = Product::query()->updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => 'Demo Product '.$index,
                    'slug' => 'demo-product-'.$index,
                    'short_description' => 'Demo short description '.$index,
                    'description' => 'Demo long description '.$index,
                    'status' => ProductStatus::ACTIVE->value,
                    'is_featured' => $index % 5 === 0,
                    'category_id' => $category->id,
                    'brand' => 'Brand '.chr(64 + ($index % 5 + 1)),
                    'weight_grams' => 100 + $index,
                    'meta_title' => 'Demo Product '.$index,
                    'meta_description' => 'Meta description for demo product '.$index,
                    'published_at' => now()->subDays(random_int(1, 60)),
                ],
            );

            foreach (range(1, 2) as $variantIndex) {
                $variantSku = $sku.'-V'.$variantIndex;
                $price = 10 + ($index * 3) + $variantIndex;

                $variant = ProductVariant::query()->updateOrCreate(
                    ['sku' => $variantSku],
                    [
                        'product_id' => $product->id,
                        'name' => $product->name.' Variant '.$variantIndex,
                        'attributes' => [
                            'size' => $variantIndex === 1 ? 'M' : 'L',
                            'color' => $variantIndex === 1 ? 'black' : 'white',
                        ],
                        'price' => $price,
                        'compare_at_price' => $price + 5,
                        'currency' => 'USD',
                        'is_active' => true,
                    ],
                );

                Inventory::query()->updateOrCreate(
                    ['product_variant_id' => $variant->id],
                    [
                        'quantity' => 80,
                        'reserved_quantity' => 0,
                        'low_stock_threshold' => 5,
                    ],
                );

                Price::query()->updateOrCreate(
                    [
                        'product_variant_id' => $variant->id,
                        'currency' => 'USD',
                        'starts_at' => null,
                        'ends_at' => null,
                    ],
                    [
                        'amount' => $price,
                    ],
                );
            }
        }
    }
}
