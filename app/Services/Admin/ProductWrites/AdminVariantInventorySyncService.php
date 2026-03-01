<?php

declare(strict_types=1);

namespace App\Services\Admin\ProductWrites;

use App\Application\Admin\Products\Dto\AdminProductVariantInputDto;
use App\Models\Inventory;
use App\Models\ProductVariant;

final class AdminVariantInventorySyncService
{
    /**
     * Persist variant inventory state.
     */
    public function sync(ProductVariant $variant, AdminProductVariantInputDto $variantInput): void
    {
        Inventory::query()->updateOrCreate(
            ['product_variant_id' => $variant->id],
            $variantInput->inventory->toPersistenceAttributes()
        );
    }
}
