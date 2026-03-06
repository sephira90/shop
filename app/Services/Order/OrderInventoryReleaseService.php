<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Domain\Exceptions\OrderTransitionException;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Data\TypedValue;

final readonly class OrderInventoryReleaseService
{
    /**
     * Restore consumed inventory for a cancelled order.
     */
    public function release(Order $order): void
    {
        $releaseQuantityByVariant = [];

        $orderItems = OrderItem::query()
            ->where('order_id', $order->id)
            ->whereNotNull('product_variant_id')
            ->selectRaw('product_variant_id, SUM(quantity) as release_quantity')
            ->groupBy('product_variant_id')
            ->orderBy('product_variant_id')
            ->get();

        foreach ($orderItems as $orderItem) {
            $variantId = TypedValue::int($orderItem->getAttribute('product_variant_id'));
            $releaseQuantityByVariant[$variantId] = TypedValue::int($orderItem->getAttribute('release_quantity'));
        }

        if ($releaseQuantityByVariant === []) {
            return;
        }

        $inventories = Inventory::query()
            ->whereIn('product_variant_id', array_keys($releaseQuantityByVariant))
            ->orderBy('product_variant_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_variant_id');

        if ($inventories->count() !== count($releaseQuantityByVariant)) {
            throw OrderTransitionException::inventoryRowNotFoundForRelease();
        }

        foreach ($releaseQuantityByVariant as $variantId => $releaseQuantity) {
            $inventory = $inventories->get($variantId);

            if (! $inventory instanceof Inventory) {
                throw OrderTransitionException::inventoryRowNotFoundForRelease();
            }

            $inventory->increment('quantity', $releaseQuantity);
        }
    }
}
