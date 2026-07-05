<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Dto;

use App\Models\OrderItem;

final readonly class AccountOrderItemResultDto
{
    public static function fromOrderItem(OrderItem $item): self
    {
        return new self(
            productVariantId: is_numeric($item->product_variant_id) ? (int) $item->product_variant_id : null,
            sku: (string) $item->sku,
            name: (string) $item->name,
            quantity: (int) $item->quantity,
            unitPrice: (float) $item->unit_price,
            totalPrice: (float) $item->total_price,
        );
    }

    public function __construct(
        public ?int $productVariantId,
        public string $sku,
        public string $name,
        public int $quantity,
        public float $unitPrice,
        public float $totalPrice,
    ) {}

    /**
     * @return array{
     *     product_variant_id:int|null,
     *     sku:string,
     *     name:string,
     *     quantity:int,
     *     unit_price:float,
     *     total_price:float
     * }
     */
    public function toArray(): array
    {
        return [
            'product_variant_id' => $this->productVariantId,
            'sku' => $this->sku,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'total_price' => $this->totalPrice,
        ];
    }
}
