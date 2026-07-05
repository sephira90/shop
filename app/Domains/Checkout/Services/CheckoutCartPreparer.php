<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Services;

use App\Domain\Exceptions\CheckoutException;
use App\Domain\ValueObjects\Money;
use App\Domains\Checkout\Application\Dto\CheckoutCartLineItemDto;
use App\Domains\Checkout\Application\Dto\CheckoutCartPreparationDto;
use App\Enums\CartStatus;
use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Data\TypedValue;

final class CheckoutCartPreparer
{
    public function prepare(Cart $lockedCart, string $currency): CheckoutCartPreparationDto
    {
        if ($lockedCart->items->isEmpty()) {
            throw CheckoutException::cartIsEmpty();
        }

        if (TypedValue::string($lockedCart->getRawOriginal('status')) !== CartStatus::ACTIVE->value) {
            throw CheckoutException::cartNotActiveForCheckout();
        }

        $subtotal = Money::zero($currency);
        $lineItems = [];
        $requiredQuantityByVariant = [];

        foreach ($lockedCart->items as $item) {
            $variant = $item->variant;
            if (! $variant instanceof ProductVariant) {
                throw CheckoutException::cartContainsUnavailableItems();
            }

            $product = $variant->product;
            if (! $product instanceof Product || ! $variant->is_active
                || TypedValue::string($product->getRawOriginal('status')) !== ProductStatus::ACTIVE->value
                || $product->published_at === null) {
                throw CheckoutException::cartContainsUnavailableItems();
            }

            $variantId = (int) $item->product_variant_id;
            $requiredQuantityByVariant[$variantId] = ($requiredQuantityByVariant[$variantId] ?? 0) + $item->quantity;
            $lineTotal = Money::fromDecimal((float) $item->line_total, $currency);
            $subtotal = $subtotal->add($lineTotal);

            $lineItems[] = new CheckoutCartLineItemDto(
                productVariantId: $variantId,
                sku: $variant->sku,
                name: $variant->name,
                quantity: $item->quantity,
                unitPrice: Money::fromDecimal((float) $item->unit_price, $currency),
                lineTotal: $lineTotal,
            );
        }

        return new CheckoutCartPreparationDto(
            subtotal: $subtotal,
            requiredQuantityByVariant: $requiredQuantityByVariant,
            lineItems: $lineItems,
        );
    }
}
