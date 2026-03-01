<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Dto;

final readonly class AdminCategoryOptionListFilterDto
{
    /**
     * Create selector filter object.
     */
    public function __construct(
        public ?string $search,
        public ?int $excludeId,
    ) {}

    /**
     * Build selector filter from validated query payload.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            search: self::normalizeSearch($validated['q'] ?? null),
            excludeId: self::normalizeId($validated['exclude_id'] ?? null),
        );
    }

    private static function normalizeSearch(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $search = trim($value);

        return $search !== '' ? $search : null;
    }

    private static function normalizeId(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }
}
