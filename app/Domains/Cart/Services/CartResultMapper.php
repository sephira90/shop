<?php

declare(strict_types=1);

namespace App\Domains\Cart\Services;

use App\Domain\ValueObjects\Money;
use App\Domains\Cart\Application\Dto\CartItemResultDto;
use App\Domains\Cart\Application\Dto\CartResultDto;
use App\Domains\Cart\Application\Dto\CartSummaryResultDto;
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
        $currency = TypedValue::nullableTrimmedString($cart->currency) ?? 'USD';
        $subtotal = Money::zero($currency);
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

            $unitPrice = Money::fromDecimal((float) $item->unit_price, $currency);
            $lineTotal = Money::fromDecimal((float) $item->line_total, $currency);
            $subtotal = $subtotal->add($lineTotal);

            $items[] = new CartItemResultDto(
                productVariantId: (int) $item->product_variant_id,
                sku: $sku,
                name: $name,
                quantity: (int) $item->quantity,
                unitPrice: $unitPrice->toFloat(),
                lineTotal: $lineTotal->toFloat(),
            );
        }

        return new CartResultDto(
            id: (string) $cart->id,
            guestToken: $cart->guest_token,
            currency: $currency,
            status: $statusValue,
            items: $items,
            summary: new CartSummaryResultDto(
                subtotal: $subtotal->toFloat(),
                discountTotal: 0.0,
                shippingTotal: 0.0,
                total: $subtotal->toFloat(),
            ),
        );
    }
}
