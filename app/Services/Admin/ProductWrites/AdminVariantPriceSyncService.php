<?php

declare(strict_types=1);

namespace App\Services\Admin\ProductWrites;

use App\Models\Price;
use App\Models\ProductVariant;

final class AdminVariantPriceSyncService
{
    /**
     * Sync current variant price to prices table.
     */
    public function sync(ProductVariant $variant): void
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
