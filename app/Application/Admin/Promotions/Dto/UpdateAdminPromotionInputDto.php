<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Dto;

final readonly class UpdateAdminPromotionInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            name: trim((string) $validated['name']),
            hasCode: array_key_exists('code', $validated),
            code: self::normalizeNullableString($validated['code'] ?? null),
            type: trim((string) $validated['type']),
            value: self::normalizeFloat($validated['value']),
            isActive: (bool) ($validated['is_active'] ?? true),
            startsAt: self::normalizeNullableString($validated['starts_at'] ?? null),
            endsAt: self::normalizeNullableString($validated['ends_at'] ?? null),
            usageLimit: self::normalizeNullableInteger($validated['usage_limit'] ?? null),
        );
    }

    public function __construct(
        public string $name,
        public bool $hasCode,
        public ?string $code,
        public string $type,
        public float $value,
        public bool $isActive,
        public ?string $startsAt,
        public ?string $endsAt,
        public ?int $usageLimit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toPromotionAttributes(): array
    {
        $attributes = [
            'name' => $this->name,
            'type' => $this->type,
            'value' => $this->value,
            'is_active' => $this->isActive,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'usage_limit' => $this->usageLimit,
        ];

        if ($this->hasCode) {
            $attributes['code'] = $this->code;
        }

        return $attributes;
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
     * Normalize float input.
     */
    private static function normalizeFloat(mixed $value): float
    {
        return (float) $value;
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
}
