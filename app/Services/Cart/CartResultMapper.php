<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Application\Cart\Dto\CartItemResultDto;
use App\Application\Cart\Dto\CartResultDto;
use App\Application\Cart\Dto\CartSummaryResultDto;
use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\ProductVariant;
use App\Support\Data\TypedValue;

final class CartResultMapper
{
    /**
     * Build normalized cart result DTO.
     */
    public function toResultDto(Cart $cart): CartResultDto
    {
        $subtotal = TypedValue::float($cart->items->sum('line_total'));
        $statusValue = TypedValue::nullableTrimmedString($cart->getRawOriginal('status')) ?? '';

        if ($statusValue === '') {
            $status = $cart->getAttribute('status');
            $statusValue = $status instanceof CartStatus ? $status->value : TypedValue::string($status);
        }

        $items = [];

        foreach ($cart->items as $item) {
            $variant = $item->variant;
            $sku = '';
            $name = '';

            if ($variant instanceof ProductVariant) {
                $sku = (string) $variant->sku;
                $name = (string) $variant->name;
            }

            $items[] = new CartItemResultDto(
                productVariantId: (int) $item->product_variant_id,
                sku: $sku,
                name: $name,
                quantity: (int) $item->quantity,
                unitPrice: (float) $item->unit_price,
                lineTotal: (float) $item->line_total,
            );
        }

        return new CartResultDto(
            id: (string) $cart->id,
            guestToken: $cart->guest_token,
            currency: $cart->currency,
            status: $statusValue,
            items: $items,
            summary: new CartSummaryResultDto(
                subtotal: $subtotal,
                discountTotal: 0.0,
                shippingTotal: 0.0,
                total: $subtotal,
            ),
        );
    }
}
