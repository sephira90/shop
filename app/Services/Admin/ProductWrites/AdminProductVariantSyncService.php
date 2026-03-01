<?php

declare(strict_types=1);

namespace App\Services\Admin\ProductWrites;

use App\Application\Admin\Products\Dto\AdminProductVariantInputDto;
use App\Models\Product;
use App\Models\ProductVariant;

final class AdminProductVariantSyncService
{
    public function __construct(
        private readonly AdminProductVariantResolver $adminProductVariantResolver,
        private readonly AdminVariantInventorySyncService $adminVariantInventorySyncService,
        private readonly AdminVariantPriceSyncService $adminVariantPriceSyncService,
    ) {}

    /**
     * Sync full variant set for a product.
     *
     * @param  iterable<int, AdminProductVariantInputDto>  $variants
     */
    public function sync(Product $product, iterable $variants): void
    {
        $keptVariantIds = [];

        foreach ($variants as $index => $variantInput) {
            $variant = $this->adminProductVariantResolver->resolve($product, $variantInput, $index);
            $preparedPayload = $variantInput->toPersistenceAttributes();

            if ($variant instanceof ProductVariant) {
                $variant->update($preparedPayload);
            } else {
                /** @var ProductVariant $variant */
                $variant = $product->variants()->create($preparedPayload);
            }

            $keptVariantIds[] = $variant->id;
            $this->adminVariantInventorySyncService->sync($variant, $variantInput);
            $this->adminVariantPriceSyncService->sync($variant);
        }

        if ($keptVariantIds === []) {
            return;
        }

        $staleVariants = $product->variants()
            ->whereNotIn('id', $keptVariantIds)
            ->get();

        foreach ($staleVariants as $staleVariant) {
            $staleVariant->delete();
        }
    }
}
