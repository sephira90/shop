<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Application\Dto;

use App\Models\Inventory;
use App\Models\ProductVariant;

final readonly class CatalogProductVariantInventoryResultDto
{
    public static function fromVariant(ProductVariant $variant): self
    {
        if (! $variant->relationLoaded('inventory')) {
            return new self(
                quantity: null,
                reservedQuantity: null,
                availableQuantity: null,
            );
        }

        $inventory = $variant->getRelation('inventory');

        if (! $inventory instanceof Inventory) {
            return new self(
                quantity: null,
                reservedQuantity: null,
                availableQuantity: null,
            );
        }

        return new self(
            quantity: (int) $inventory->quantity,
            reservedQuantity: (int) $inventory->reserved_quantity,
            availableQuantity: $inventory->availableQuantity(),
        );
    }

    public function __construct(
        public ?int $quantity,
        public ?int $reservedQuantity,
        public ?int $availableQuantity,
    ) {}

    /**
     * @return array{
     *     quantity:int|null,
     *     reserved_quantity:int|null,
     *     available_quantity:int|null
     * }
     */
    public function toArray(): array
    {
        return [
            'quantity' => $this->quantity,
            'reserved_quantity' => $this->reservedQuantity,
            'available_quantity' => $this->availableQuantity,
        ];
    }
}
