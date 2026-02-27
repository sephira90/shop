<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Dto;

final readonly class AdminCategoryListFilterDto
{
    /**
     * Create filter object.
     */
    public function __construct(
        public int $page,
        public int $perPage,
        public ?string $search,
        public ?bool $isActive,
    ) {}

    /**
     * Build filter from validated query payload.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: max(1, (int) ($validated['per_page'] ?? 30)),
            search: self::normalizeSearch($validated['q'] ?? null),
            isActive: self::normalizeBoolean($validated['is_active'] ?? null),
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

    /**
     * Normalize boolean-like payload from query string.
     */
    private static function normalizeBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                1 => true,
                0 => false,
                default => null,
            };
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = strtolower(trim($value));

        return match ($normalized) {
            '1', 'true', 'on', 'yes' => true,
            '0', 'false', 'off', 'no' => false,
            default => null,
        };
    }
}
