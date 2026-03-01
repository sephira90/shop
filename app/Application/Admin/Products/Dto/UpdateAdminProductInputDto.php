<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Dto;

use App\Support\Data\TypedValue;

final readonly class UpdateAdminProductInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            sku: TypedValue::trimmedString($validated['sku']),
            name: TypedValue::trimmedString($validated['name']),
            status: TypedValue::trimmedString($validated['status']),
            variants: self::normalizeVariants($validated),
            optionalAttributes: self::normalizeOptionalAttributes($validated),
        );
    }

    /**
     * @param  array<int, AdminProductVariantInputDto>|null  $variants
     * @param  array<string, mixed>  $optionalAttributes
     */
    public function __construct(
        public string $sku,
        public string $name,
        public string $status,
        public ?array $variants,
        private array $optionalAttributes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toPersistenceAttributes(): array
    {
        return array_merge([
            'sku' => $this->sku,
            'name' => $this->name,
            'status' => $this->status,
        ], $this->optionalAttributes);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, AdminProductVariantInputDto>|null
     */
    private static function normalizeVariants(array $validated): ?array
    {
        if (! array_key_exists('variants', $validated) || ! is_array($validated['variants'])) {
            return null;
        }

        $variants = [];

        foreach ($validated['variants'] as $variantPayload) {
            if (! is_array($variantPayload)) {
                continue;
            }

            $variants[] = AdminProductVariantInputDto::fromValidated(TypedValue::associativeArray($variantPayload));
        }

        return $variants;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private static function normalizeOptionalAttributes(array $validated): array
    {
        $optional = [];

        if (array_key_exists('slug', $validated)) {
            $optional['slug'] = self::normalizeNullableString($validated['slug']);
        }
        if (array_key_exists('short_description', $validated)) {
            $optional['short_description'] = self::normalizeNullableString($validated['short_description']);
        }
        if (array_key_exists('description', $validated)) {
            $optional['description'] = self::normalizeNullableString($validated['description']);
        }
        if (array_key_exists('is_featured', $validated)) {
            $optional['is_featured'] = $validated['is_featured'] === null
                ? null
                : (bool) $validated['is_featured'];
        }
        if (array_key_exists('category_id', $validated)) {
            $optional['category_id'] = self::normalizeNullableInteger($validated['category_id']);
        }
        if (array_key_exists('brand', $validated)) {
            $optional['brand'] = self::normalizeNullableString($validated['brand']);
        }
        if (array_key_exists('weight_grams', $validated)) {
            $optional['weight_grams'] = self::normalizeNullableInteger($validated['weight_grams']);
        }
        if (array_key_exists('meta_title', $validated)) {
            $optional['meta_title'] = self::normalizeNullableString($validated['meta_title']);
        }
        if (array_key_exists('meta_description', $validated)) {
            $optional['meta_description'] = self::normalizeNullableString($validated['meta_description']);
        }
        if (array_key_exists('published_at', $validated)) {
            $optional['published_at'] = self::normalizeNullableString($validated['published_at']);
        }

        return $optional;
    }

    private static function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }

    private static function normalizeNullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }
}
