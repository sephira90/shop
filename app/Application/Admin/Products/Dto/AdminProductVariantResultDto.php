<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Dto;

use App\Models\Inventory;
use App\Models\ProductVariant;

final readonly class AdminProductVariantResultDto
{
    public static function fromVariant(ProductVariant $variant): self
    {
        $inventory = null;
        if ($variant->relationLoaded('inventory')) {
            $loadedInventory = $variant->getRelation('inventory');
            if ($loadedInventory instanceof Inventory) {
                $inventory = $loadedInventory;
            }
        }

        return new self(
            id: $variant->id,
            sku: (string) $variant->sku,
            name: (string) $variant->name,
            attributes: self::normalizeAttributes($variant->attributes),
            price: (float) $variant->price,
            compareAtPrice: $variant->compare_at_price !== null ? (float) $variant->compare_at_price : null,
            currency: (string) $variant->currency,
            isActive: (bool) $variant->is_active,
            inventory: AdminProductVariantInventoryResultDto::fromInventory($inventory),
        );
    }

    /**
     * @param  array<string, mixed>|object|null  $attributes
     */
    public function __construct(
        public int $id,
        public string $sku,
        public string $name,
        public array|object|null $attributes,
        public float $price,
        public ?float $compareAtPrice,
        public string $currency,
        public bool $isActive,
        public AdminProductVariantInventoryResultDto $inventory,
    ) {}

    /**
     * @return array{
     *     id:int,
     *     sku:string,
     *     name:string,
     *     attributes:array<string,mixed>|object|null,
     *     price:float,
     *     compare_at_price:float|null,
     *     currency:string,
     *     is_active:bool,
     *     inventory:array{
     *         quantity:int|null,
     *         reserved_quantity:int|null,
     *         available_quantity:int|null
     *     }
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'attributes' => $this->attributes,
            'price' => $this->price,
            'compare_at_price' => $this->compareAtPrice,
            'currency' => $this->currency,
            'is_active' => $this->isActive,
            'inventory' => $this->inventory->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>|object|null
     */
    private static function normalizeAttributes(mixed $attributes): array|object|null
    {
        if ($attributes === null) {
            return null;
        }

        if (! is_array($attributes)) {
            return null;
        }

        if ($attributes === []) {
            return (object) [];
        }

        if (array_is_list($attributes)) {
            return null;
        }

        return $attributes;
    }
}
