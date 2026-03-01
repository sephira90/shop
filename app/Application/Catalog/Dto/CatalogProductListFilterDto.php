<?php

declare(strict_types=1);

namespace App\Application\Catalog\Dto;

use App\Support\Data\TypedValue;

final readonly class CatalogProductListFilterDto
{
    /**
     * Create typed catalog list filter DTO.
     */
    public function __construct(
        public ?string $search,
        public ?string $categorySlug,
        public ?float $minPrice,
        public ?float $maxPrice,
        public string $sort,
    ) {}

    /**
     * Build DTO from validated transport payload.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        $search = isset($validated['q']) ? TypedValue::trimmedString($validated['q']) : null;
        $categorySlug = isset($validated['category_slug']) ? TypedValue::trimmedString($validated['category_slug']) : null;
        $sort = isset($validated['sort']) ? TypedValue::string($validated['sort']) : 'newest';

        return new self(
            search: $search !== '' ? $search : null,
            categorySlug: $categorySlug !== '' ? $categorySlug : null,
            minPrice: isset($validated['min_price']) ? TypedValue::float($validated['min_price']) : null,
            maxPrice: isset($validated['max_price']) ? TypedValue::float($validated['max_price']) : null,
            sort: $sort,
        );
    }

    /**
     * Normalize DTO to deterministic cache key payload.
     *
     * @return array{
     *   q:?string,
     *   category_slug:?string,
     *   min_price:?float,
     *   max_price:?float,
     *   sort:string
     * }
     */
    public function toCachePayload(): array
    {
        return [
            'q' => $this->search,
            'category_slug' => $this->categorySlug,
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'sort' => $this->sort,
        ];
    }
}
