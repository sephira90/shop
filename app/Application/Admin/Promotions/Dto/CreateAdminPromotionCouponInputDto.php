<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Dto;

use InvalidArgumentException;
use LogicException;

final readonly class CreateAdminPromotionCouponInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            hasCode: array_key_exists('code', $validated),
            code: self::normalizeNullableString($validated['code'] ?? null),
            isActive: (bool) ($validated['is_active'] ?? true),
            maxRedemptions: self::normalizeNullableInteger($validated['max_redemptions'] ?? null),
            expiresAt: self::normalizeNullableString($validated['expires_at'] ?? null),
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidatedWithRequiredCode(array $validated): self
    {
        $dto = self::fromValidated($validated);

        if (! $dto->hasCode || $dto->code === null) {
            throw new InvalidArgumentException('Coupon code is required.');
        }

        return $dto;
    }

    public function __construct(
        public bool $hasCode,
        public ?string $code,
        public bool $isActive,
        public ?int $maxRedemptions,
        public ?string $expiresAt,
    ) {}

    /**
     * Return normalized coupon code when code is mandatory.
     */
    public function requiredCode(): string
    {
        if ($this->code === null) {
            throw new LogicException('Coupon code is required for this operation.');
        }

        return $this->code;
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
}
