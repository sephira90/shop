<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Application\Dto;

use App\Models\ProductVariant;
use App\Support\Data\TypedValue;

final readonly class CatalogProductVariantResultDto
{
    public static function fromVariant(ProductVariant $variant): self
    {
        return new self(
            id: (int) $variant->id,
            sku: (string) $variant->sku,
            name: (string) $variant->name,
            attributes: self::normalizeVariantAttributes($variant->attributes),
            price: (float) $variant->price,
            compareAtPrice: is_numeric($variant->compare_at_price) ? (float) $variant->compare_at_price : null,
            currency: (string) $variant->currency,
            isActive: (bool) $variant->is_active,
            inventory: CatalogProductVariantInventoryResultDto::fromVariant($variant),
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
        public CatalogProductVariantInventoryResultDto $inventory,
    ) {}

    /**
     * @return array{
     *     id:int,
     *     sku:string,
     *     name:string,
     *     attributes:array<string, mixed>|object|null,
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
    private static function normalizeVariantAttributes(mixed $attributes): array|object|null
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

        return TypedValue::associativeArray($attributes);
    }
}
