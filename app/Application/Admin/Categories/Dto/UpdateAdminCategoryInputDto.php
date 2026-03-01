<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Dto;

use App\Support\Data\TypedValue;
use Illuminate\Support\Str;

final readonly class UpdateAdminCategoryInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated, string $existingSlug): self
    {
        $name = TypedValue::trimmedString($validated['name']);
        $slug = self::normalizeNullableString($validated['slug'] ?? null);
        $normalizedExistingSlug = trim($existingSlug);

        if ($slug === null) {
            $slug = $normalizedExistingSlug !== '' ? $normalizedExistingSlug : Str::slug($name);
        }

        return new self(
            parentId: self::normalizeNullableInteger($validated['parent_id'] ?? null),
            name: $name,
            slug: $slug,
            description: self::normalizeNullableString($validated['description'] ?? null),
            metaTitle: self::normalizeNullableString($validated['meta_title'] ?? null),
            metaDescription: self::normalizeNullableString($validated['meta_description'] ?? null),
            isActive: (bool) ($validated['is_active'] ?? false),
            sortOrder: self::normalizeInteger($validated['sort_order'] ?? 0, 0),
        );
    }

    public function __construct(
        public ?int $parentId,
        public string $name,
        public string $slug,
        public ?string $description,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public bool $isActive,
        public int $sortOrder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toPersistenceAttributes(): array
    {
        return [
            'parent_id' => $this->parentId,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
        ];
    }

    /**
     * Normalize nullable string input.
     */
    private static function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * Normalize nullable integer input.
     */
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

    /**
     * Normalize integer input.
     */
    private static function normalizeInteger(mixed $value, int $fallback): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $fallback;
    }
}
