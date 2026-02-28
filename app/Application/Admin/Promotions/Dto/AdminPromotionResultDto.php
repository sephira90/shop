<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Dto;

use App\Models\Coupon;
use App\Models\Promotion;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Support\Collection;

final readonly class AdminPromotionResultDto
{
    public static function fromPromotion(Promotion $promotion): self
    {
        /** @var list<AdminPromotionCouponResultDto> $coupons */
        $coupons = [];
        if ($promotion->relationLoaded('coupons')) {
            $loadedCoupons = $promotion->getRelation('coupons');
            if ($loadedCoupons instanceof Collection) {
                foreach ($loadedCoupons as $coupon) {
                    if ($coupon instanceof Coupon) {
                        $coupons[] = AdminPromotionCouponResultDto::fromCoupon($coupon);
                    }
                }
            }
        }

        return new self(
            id: $promotion->id,
            name: (string) $promotion->name,
            code: self::nullableString($promotion->code),
            type: self::resolveType($promotion),
            value: (float) $promotion->value,
            isActive: (bool) $promotion->is_active,
            usageLimit: self::nullableInt($promotion->usage_limit),
            usageCount: self::nullableInt($promotion->usage_count) ?? 0,
            startsAt: self::formatAtomDate($promotion->starts_at),
            endsAt: self::formatAtomDate($promotion->ends_at),
            coupons: $coupons,
            createdAt: self::formatAtomDate($promotion->created_at),
            updatedAt: self::formatAtomDate($promotion->updated_at),
        );
    }

    /**
     * @param  list<AdminPromotionCouponResultDto>  $coupons
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $code,
        public ?string $type,
        public float $value,
        public bool $isActive,
        public ?int $usageLimit,
        public int $usageCount,
        public ?string $startsAt,
        public ?string $endsAt,
        public array $coupons,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    /**
     * @return array{
     *     id:int,
     *     name:string,
     *     code:string|null,
     *     type:string|null,
     *     value:float,
     *     is_active:bool,
     *     usage_limit:int|null,
     *     usage_count:int,
     *     starts_at:string|null,
     *     ends_at:string|null,
     *     coupons:list<array{
     *         id:int,
     *         code:string,
     *         is_active:bool,
     *         max_redemptions:int|null,
     *         redeemed_count:int,
     *         expires_at:string|null
     *     }>,
     *     created_at:string|null,
     *     updated_at:string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'value' => $this->value,
            'is_active' => $this->isActive,
            'usage_limit' => $this->usageLimit,
            'usage_count' => $this->usageCount,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'coupons' => array_map(
                static fn (AdminPromotionCouponResultDto $coupon): array => $coupon->toArray(),
                $this->coupons
            ),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    private static function resolveType(Promotion $promotion): ?string
    {
        $type = $promotion->getAttribute('type');
        if ($type instanceof BackedEnum) {
            $type = $type->value;
        }

        return is_string($type) ? $type : null;
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
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
