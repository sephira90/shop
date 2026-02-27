<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Application\Admin\Products\Dto\AdminProductVariantInputDto;
use App\Application\Admin\Products\Dto\CreateAdminProductInputDto;
use App\Application\Admin\Products\Dto\UpdateAdminProductInputDto;
use App\Models\Inventory;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\CatalogVersionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AdminCatalogService
{
    /**
     * Create service instance.
     */
    public function __construct(
        private readonly CatalogVersionService $catalogVersionService,
    ) {}

    /**
     * Create product.
     */
    public function createProduct(CreateAdminProductInputDto $input): Product
    {
        return DB::transaction(function () use ($input): Product {
            $attributes = $input->toPersistenceAttributes();
            $attributes['slug'] = $attributes['slug'] ?? Str::slug($input->name);

            $product = Product::query()->create($attributes);

            if ($input->variants !== null) {
                $this->syncVariants($product, $input->variants);
            }

            $this->catalogVersionService->bump();

            return $product->fresh(['category', 'variants.inventory']);
        });
    }

    /**
     * Update product.
     */
    public function updateProduct(Product $product, UpdateAdminProductInputDto $input): Product
    {
        return DB::transaction(function () use ($product, $input): Product {
            $attributes = $input->toPersistenceAttributes();
            $attributes['slug'] = $attributes['slug'] ?? $product->slug;
            $product->update($attributes);

            if ($input->variants !== null) {
                $this->syncVariants($product, $input->variants);
            }

            $this->catalogVersionService->bump();

            return $product->fresh(['category', 'variants.inventory']);
        });
    }

    /**
     * Delete product and invalidate catalog cache.
     */
    public function deleteProduct(Product $product): void
    {
        $product->delete();
        $this->catalogVersionService->bump();
    }

    /**
     * Sync full variant set for a product.
     *
     * @param  iterable<int, AdminProductVariantInputDto>  $variants
     */
    private function syncVariants(Product $product, iterable $variants): void
    {
        $keptVariantIds = [];

        foreach ($variants as $index => $variantInput) {
            $variant = $this->resolveVariant($product, $variantInput, $index);
            $preparedPayload = $variantInput->toPersistenceAttributes();

            if ($variant instanceof ProductVariant) {
                $variant->update($preparedPayload);
            } else {
                /** @var ProductVariant $variant */
                $variant = $product->variants()->create($preparedPayload);
            }

            $keptVariantIds[] = $variant->id;
            $this->syncVariantInventory($variant, $variantInput);
            $this->syncVariantPrice($variant);
        }

        if ($keptVariantIds === []) {
            return;
        }

        $staleVariants = $product->variants()
            ->whereNotIn('id', $keptVariantIds)
            ->get();

        foreach ($staleVariants as $staleVariant) {
            if (! $staleVariant instanceof ProductVariant) {
                continue;
            }

            $staleVariant->delete();
        }
    }

    /**
     * Resolve variant by id/sku and guard against cross-product collisions.
     */
    private function resolveVariant(
        Product $product,
        AdminProductVariantInputDto $variantInput,
        int $index
    ): ?ProductVariant {
        $variantId = $variantInput->id ?? 0;

        if ($variantId > 0) {
            $variant = $product->variants()->whereKey($variantId)->first();

            if (! $variant instanceof ProductVariant) {
                throw ValidationException::withMessages([
                    "variants.$index.id" => ['Variant id does not belong to this product.'],
                ]);
            }

            $nextSku = $variantInput->sku;

            if ($nextSku !== '' && $nextSku !== $variant->sku) {
                $skuCollision = ProductVariant::query()
                    ->where('sku', $nextSku)
                    ->where('id', '!=', $variant->id)
                    ->first();

                if ($skuCollision instanceof ProductVariant) {
                    throw ValidationException::withMessages([
                        "variants.$index.sku" => ['Variant SKU already exists.'],
                    ]);
                }
            }

            return $variant;
        }

        $sku = $variantInput->sku;

        if ($sku === '') {
            return null;
        }

        $ownVariant = $product->variants()->where('sku', $sku)->first();

        if ($ownVariant instanceof ProductVariant) {
            return $ownVariant;
        }

        $existingVariant = ProductVariant::query()->where('sku', $sku)->first();

        if ($existingVariant instanceof ProductVariant) {
            throw ValidationException::withMessages([
                "variants.$index.sku" => ['Variant SKU already exists.'],
            ]);
        }

        return null;
    }

    /**
     * Persist variant inventory state.
     */
    private function syncVariantInventory(
        ProductVariant $variant,
        AdminProductVariantInputDto $variantInput
    ): void {
        Inventory::query()->updateOrCreate(
            ['product_variant_id' => $variant->id],
            $variantInput->inventory->toPersistenceAttributes()
        );
    }

    /**
     * Sync current variant price to prices table.
     */
    private function syncVariantPrice(ProductVariant $variant): void
    {
        Price::query()->updateOrCreate(
            [
                'product_variant_id' => $variant->id,
                'currency' => $variant->currency,
                'starts_at' => null,
                'ends_at' => null,
            ],
            [
                'amount' => $variant->price,
            ]
        );
    }
}
