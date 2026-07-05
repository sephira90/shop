<?php

declare(strict_types=1);

namespace App\Domains\Cart\Application\Dto;

final readonly class CartItemResultDto
{
    public function __construct(
        public int $productVariantId,
        public string $sku,
        public string $name,
        public int $quantity,
        public float $unitPrice,
        public float $lineTotal,
    ) {}

    /**
     * @return array<string, int|float|string>
     */
    public function toArray(): array
    {
        return [
            'product_variant_id' => $this->productVariantId,
            'sku' => $this->sku,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'line_total' => $this->lineTotal,
        ];
    }
}
