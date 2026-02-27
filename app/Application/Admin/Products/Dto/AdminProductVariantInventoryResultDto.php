<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Dto;

use App\Models\Inventory;

final readonly class AdminProductVariantInventoryResultDto
{
    public static function fromInventory(?Inventory $inventory): self
    {
        if (! $inventory instanceof Inventory) {
            return new self(
                quantity: null,
                reservedQuantity: null,
                availableQuantity: null,
            );
        }

        return new self(
            quantity: $inventory->quantity,
            reservedQuantity: $inventory->reserved_quantity,
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
