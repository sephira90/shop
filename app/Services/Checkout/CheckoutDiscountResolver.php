<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Application\Checkout\Dto\CheckoutPlaceOrderInputDto;
use App\Domain\ValueObjects\Money;
use App\Enums\PromotionType;
use App\Models\Coupon;
use App\Models\Promotion;
use App\Services\Checkout\Dto\CheckoutDiscountContextDto;
use Carbon\Carbon;
use DomainException;

final class CheckoutDiscountResolver
{
    public function resolve(CheckoutPlaceOrderInputDto $checkoutInput, Money $subtotal): CheckoutDiscountContextDto
    {
        $couponCode = $checkoutInput->couponCode ?? '';

        if ($couponCode === '') {
            return new CheckoutDiscountContextDto(
                discountTotal: Money::zero($subtotal->currency()),
                coupon: null,
                promotion: null,
            );
        }

        $coupon = Coupon::query()
            ->where('code', $couponCode)
            ->lockForUpdate()
            ->first();

        if (! $coupon instanceof Coupon || ! $coupon->is_active) {
            throw new DomainException('Coupon code is invalid.');
        }

        /** @var Carbon|null $couponExpiresAt */
        $couponExpiresAt = $coupon->expires_at;

        if ($couponExpiresAt !== null && now()->isAfter($couponExpiresAt)) {
            throw new DomainException('Coupon has expired.');
        }

        if ($coupon->max_redemptions !== null && $coupon->redeemed_count >= $coupon->max_redemptions) {
            throw new DomainException('Coupon usage limit exceeded.');
        }

        $promotion = Promotion::query()
            ->whereKey($coupon->promotion_id)
            ->lockForUpdate()
            ->first();

        if (! $promotion instanceof Promotion || ! $promotion->is_active) {
            throw new DomainException('Promotion is not available.');
        }

        /** @var Carbon|null $promotionStartsAt */
        $promotionStartsAt = $promotion->starts_at;
        if ($promotionStartsAt !== null && now()->isBefore($promotionStartsAt)) {
            throw new DomainException('Promotion has not started yet.');
        }

        /** @var Carbon|null $promotionEndsAt */
        $promotionEndsAt = $promotion->ends_at;
        if ($promotionEndsAt !== null && now()->isAfter($promotionEndsAt)) {
            throw new DomainException('Promotion has ended.');
        }

        if ($promotion->usage_limit !== null && $promotion->usage_count >= $promotion->usage_limit) {
            throw new DomainException('Promotion usage limit exceeded.');
        }

        return new CheckoutDiscountContextDto(
            discountTotal: $this->calculateDiscountTotal($promotion->type, (float) $promotion->value, $subtotal),
            coupon: $coupon,
            promotion: $promotion,
        );
    }

    /**
     * Calculate discount amount by promotion type.
     */
    private function calculateDiscountTotal(PromotionType|string $type, float $promotionValue, Money $subtotal): Money
    {
        try {
            $promotionType = $type instanceof PromotionType ? $type : PromotionType::from($type);
        } catch (\ValueError $exception) {
            throw new DomainException('Promotion type is invalid.', 0, $exception);
        }

        $discount = match ($promotionType) {
            PromotionType::PERCENT => $subtotal->percentage($promotionValue)->min($subtotal),
            PromotionType::FIXED => Money::fromDecimal($promotionValue, $subtotal->currency())->min($subtotal),
        };

        if ($discount->amountCents() <= 0) {
            return Money::zero($subtotal->currency());
        }

        return $discount;
    }
}
