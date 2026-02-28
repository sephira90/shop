<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Dto;

use App\Models\Coupon;
use DateTimeInterface;

final readonly class AdminPromotionCouponResultDto
{
    public static function fromCoupon(Coupon $coupon): self
    {
        return new self(
            id: $coupon->id,
            code: (string) $coupon->code,
            isActive: (bool) $coupon->is_active,
            maxRedemptions: self::nullableInt($coupon->max_redemptions),
            redeemedCount: self::nullableInt($coupon->redeemed_count) ?? 0,
            expiresAt: self::formatAtomDate($coupon->expires_at),
        );
    }

    public function __construct(
        public int $id,
        public string $code,
        public bool $isActive,
        public ?int $maxRedemptions,
        public int $redeemedCount,
        public ?string $expiresAt,
    ) {}

    /**
     * @return array{
     *     id:int,
     *     code:string,
     *     is_active:bool,
     *     max_redemptions:int|null,
     *     redeemed_count:int,
     *     expires_at:string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'is_active' => $this->isActive,
            'max_redemptions' => $this->maxRedemptions,
            'redeemed_count' => $this->redeemedCount,
            'expires_at' => $this->expiresAt,
        ];
    }

    private static function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function formatAtomDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return $value;
    }
}
