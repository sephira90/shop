<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Application\Checkout\Dto\CheckoutCartLineItemDto;
use App\Application\Checkout\Dto\CheckoutCartPreparationDto;
use App\Enums\CartStatus;
use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use DomainException;

final class CheckoutCartPreparer
{
    public function prepare(Cart $lockedCart): CheckoutCartPreparationDto
    {
        if ($lockedCart->items->isEmpty()) {
            throw new DomainException('Cart is empty.');
        }

        if ((string) $lockedCart->getRawOriginal('status') !== CartStatus::ACTIVE->value) {
            throw new DomainException('Cart is not active for checkout.');
        }

        $subtotal = 0.0;
        $lineItems = [];
        $requiredQuantityByVariant = [];

        foreach ($lockedCart->items as $item) {
            if (! $item instanceof CartItem) {
                throw new DomainException('Cart item payload is invalid.');
            }

            $variant = $item->variant;
            if (! $variant instanceof ProductVariant) {
                throw new DomainException('Cart contains unavailable items.');
            }

            $product = $variant->product;
            if (! $product instanceof Product || ! $variant->is_active
                || (string) $product->getRawOriginal('status') !== ProductStatus::ACTIVE->value
                || $product->published_at === null) {
                throw new DomainException('Cart contains unavailable items.');
            }

            $variantId = (int) $item->product_variant_id;
            $requiredQuantityByVariant[$variantId] = ($requiredQuantityByVariant[$variantId] ?? 0) + $item->quantity;
            $subtotal += (float) $item->line_total;

            $lineItems[] = new CheckoutCartLineItemDto(
                productVariantId: $variantId,
                sku: $variant->sku,
                name: $variant->name,
                quantity: $item->quantity,
                unitPrice: (float) $item->unit_price,
                lineTotal: (float) $item->line_total,
            );
        }

        return new CheckoutCartPreparationDto(
            subtotal: $subtotal,
            requiredQuantityByVariant: $requiredQuantityByVariant,
            lineItems: $lineItems,
        );
    }
}
