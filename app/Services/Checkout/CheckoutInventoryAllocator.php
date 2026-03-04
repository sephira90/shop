<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Application\Checkout\Dto\CheckoutInventoryDemandDto;
use App\Domain\Exceptions\CheckoutException;
use App\Models\Inventory;

final class CheckoutInventoryAllocator
{
    /**
     * Validate required quantities against locked inventory rows and consume stock.
     */
    public function assertAndConsume(CheckoutInventoryDemandDto $demand): void
    {
        $requiredQuantityByVariant = $demand->requiredQuantityByVariant;

        if ($requiredQuantityByVariant === []) {
            return;
        }

        ksort($requiredQuantityByVariant);

        $inventoryByVariant = Inventory::query()
            ->whereIn('product_variant_id', array_keys($requiredQuantityByVariant))
            ->orderBy('product_variant_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_variant_id');

        foreach ($requiredQuantityByVariant as $variantId => $requiredQuantity) {
            $inventory = $inventoryByVariant->get($variantId);

            if (! $inventory instanceof Inventory || $inventory->availableQuantity() < $requiredQuantity) {
                throw CheckoutException::insufficientStockDuringCheckout();
            }
        }

        foreach ($requiredQuantityByVariant as $variantId => $requiredQuantity) {
            /** @var Inventory $inventory */
            $inventory = $inventoryByVariant->get($variantId);
            $inventory->decrement('quantity', $requiredQuantity);
        }
    }
}
