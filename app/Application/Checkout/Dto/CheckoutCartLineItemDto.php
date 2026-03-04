<?php

declare(strict_types=1);

namespace App\Application\Checkout\Dto;

use App\Domain\ValueObjects\Money;

final readonly class CheckoutCartLineItemDto
{
    public function __construct(
        public int $productVariantId,
        public string $sku,
        public string $name,
        public int $quantity,
        public Money $unitPrice,
        public Money $lineTotal,
    ) {}

    /**
     * @return array{product_variant_id:int,sku:string,name:string,quantity:int,unit_price:float,line_total:float}
     */
    public function toCartSnapshotRow(): array
    {
        return [
            'product_variant_id' => $this->productVariantId,
            'sku' => $this->sku,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice->toFloat(),
            'line_total' => $this->lineTotal->toFloat(),
        ];
    }

    /**
     * @return array{order_id:string,product_variant_id:int,sku:string,name:string,quantity:int,unit_price:float,total_price:float,meta:string,created_at:string,updated_at:string}
     */
    public function toOrderItemInsertRow(string $orderId, string $cartId, string $timestamp): array
    {
        return [
            'order_id' => $orderId,
            'product_variant_id' => $this->productVariantId,
            'sku' => $this->sku,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice->toFloat(),
            'total_price' => $this->lineTotal->toFloat(),
            'meta' => json_encode(['source_cart' => $cartId], JSON_THROW_ON_ERROR),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }
}
