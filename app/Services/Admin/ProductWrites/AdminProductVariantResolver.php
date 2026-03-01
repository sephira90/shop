<?php

declare(strict_types=1);

namespace App\Services\Admin\ProductWrites;

use App\Application\Admin\Products\Dto\AdminProductVariantInputDto;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

final class AdminProductVariantResolver
{
    /**
     * Resolve variant by id/sku and guard against cross-product collisions.
     */
    public function resolve(
        Product $product,
        AdminProductVariantInputDto $variantInput,
        int $index,
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
}
