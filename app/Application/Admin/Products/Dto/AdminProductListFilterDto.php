<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Dto;

use App\Enums\ProductStatus;
use App\Support\Data\TypedValue;

final readonly class AdminProductListFilterDto
{
    /**
     * Create filter object.
     */
    public function __construct(
        public int $page,
        public int $perPage,
        public ?string $search,
        public ?ProductStatus $status,
        public ?int $categoryId,
    ) {}

    /**
     * Build filter from validated query payload.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        $categoryId = isset($validated['category_id']) ? TypedValue::int($validated['category_id']) : null;

        return new self(
            page: max(1, TypedValue::int($validated['page'] ?? 1)),
            perPage: max(1, TypedValue::int($validated['per_page'] ?? 30)),
            search: self::normalizeSearch($validated['q'] ?? null),
            status: isset($validated['status']) ? ProductStatus::tryFrom(TypedValue::string($validated['status'])) : null,
            categoryId: $categoryId !== null && $categoryId > 0 ? $categoryId : null,
        );
    }

    /**
     * Normalize search payload into nullable non-empty string.
     */
    private static function normalizeSearch(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $search = trim($value);

        return $search !== '' ? $search : null;
    }
}
