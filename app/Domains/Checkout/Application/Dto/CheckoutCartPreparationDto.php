<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Application\Dto;

use App\Domain\ValueObjects\Money;

final readonly class CheckoutCartPreparationDto
{
    /**
     * @param  array<int, int>  $requiredQuantityByVariant
     * @param  array<int, CheckoutCartLineItemDto>  $lineItems
     */
    public function __construct(
        public Money $subtotal,
        public array $requiredQuantityByVariant,
        public array $lineItems,
    ) {}

    /**
     * @return array<int, array{product_variant_id:int,sku:string,name:string,quantity:int,unit_price:float,line_total:float}>
     */
    public function toCartSnapshot(): array
    {
        return array_map(
            static fn (CheckoutCartLineItemDto $lineItem): array => $lineItem->toCartSnapshotRow(),
            $this->lineItems,
        );
    }

    /**
     * @return array<int, array{order_id:string,product_variant_id:int,sku:string,name:string,quantity:int,unit_price:float,total_price:float,meta:string,created_at:string,updated_at:string}>
     */
    public function toOrderItemInsertRows(string $orderId, string $cartId, string $timestamp): array
    {
        return array_map(
            static fn (CheckoutCartLineItemDto $lineItem): array => $lineItem->toOrderItemInsertRow($orderId, $cartId, $timestamp),
            $this->lineItems,
        );
    }
}
