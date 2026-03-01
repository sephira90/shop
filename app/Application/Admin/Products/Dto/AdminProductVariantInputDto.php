<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Dto;

use App\Support\Data\TypedValue;

final readonly class AdminProductVariantInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        $attributes = $validated['attributes'] ?? [];
        $normalizedAttributes = is_array($attributes) ? TypedValue::associativeArray($attributes) : [];

        return new self(
            id: self::normalizeNullableInteger($validated['id'] ?? null),
            sku: TypedValue::trimmedString($validated['sku']),
            name: TypedValue::trimmedString($validated['name']),
            attributes: $normalizedAttributes,
            price: TypedValue::float($validated['price']),
            compareAtPrice: self::normalizeNullableFloat($validated['compare_at_price'] ?? null),
            currency: self::normalizeCurrency($validated['currency'] ?? null),
            isActive: (bool) ($validated['is_active'] ?? true),
            inventory: AdminProductVariantInventoryInputDto::fromValidated($validated['inventory'] ?? null),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public ?int $id,
        public string $sku,
        public string $name,
        public array $attributes,
        public float $price,
        public ?float $compareAtPrice,
        public string $currency,
        public bool $isActive,
        public AdminProductVariantInventoryInputDto $inventory,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toPersistenceAttributes(): array
    {
        return [
            'sku' => $this->sku,
            'name' => $this->name,
            'attributes' => $this->attributes,
            'price' => $this->price,
            'compare_at_price' => $this->compareAtPrice,
            'currency' => $this->currency,
            'is_active' => $this->isActive,
        ];
    }

    private static function normalizeNullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_numeric($value)) {
            $integer = (int) $value;

            return $integer > 0 ? $integer : null;
        }

        return null;
    }

    private static function normalizeNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private static function normalizeCurrency(mixed $value): string
    {
        $currency = is_string($value) ? strtoupper(trim($value)) : '';

        return $currency !== '' ? $currency : 'USD';
    }
}
