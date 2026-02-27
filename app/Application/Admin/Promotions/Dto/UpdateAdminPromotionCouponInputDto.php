<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Dto;

final readonly class UpdateAdminPromotionCouponInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        $hasIsActive = array_key_exists('is_active', $validated);
        $hasMaxRedemptions = array_key_exists('max_redemptions', $validated);
        $hasExpiresAt = array_key_exists('expires_at', $validated);

        return new self(
            hasIsActive: $hasIsActive,
            isActive: $hasIsActive ? (bool) $validated['is_active'] : null,
            hasMaxRedemptions: $hasMaxRedemptions,
            maxRedemptions: $hasMaxRedemptions
                ? self::normalizeNullableInteger($validated['max_redemptions'])
                : null,
            hasExpiresAt: $hasExpiresAt,
            expiresAt: $hasExpiresAt ? self::normalizeNullableString($validated['expires_at']) : null,
        );
    }

    public function __construct(
        public bool $hasIsActive,
        public ?bool $isActive,
        public bool $hasMaxRedemptions,
        public ?int $maxRedemptions,
        public bool $hasExpiresAt,
        public ?string $expiresAt,
    ) {}

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
}
