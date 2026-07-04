<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Application\Checkout\Dto\CheckoutPlaceOrderInputDto;
use App\Domain\Exceptions\CheckoutException;
use App\Domain\ValueObjects\Money;
use App\Enums\PromotionType;
use App\Models\Coupon;
use App\Models\Promotion;
use App\Services\Checkout\Dto\CheckoutDiscountContextDto;
use Carbon\Carbon;

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
            throw CheckoutException::couponCodeInvalid();
        }

        /** @var Carbon|null $couponExpiresAt */
        $couponExpiresAt = $coupon->expires_at;

        if ($couponExpiresAt !== null && now()->isAfter($couponExpiresAt)) {
            throw CheckoutException::couponExpired();
        }

        if ($coupon->max_redemptions !== null && $coupon->redeemed_count >= $coupon->max_redemptions) {
            throw CheckoutException::couponUsageLimitExceeded();
        }

        $promotion = Promotion::query()
            ->whereKey($coupon->promotion_id)
            ->lockForUpdate()
            ->first();

        if (! $promotion instanceof Promotion || ! $promotion->is_active) {
            throw CheckoutException::promotionNotAvailable();
        }

        /** @var Carbon|null $promotionStartsAt */
        $promotionStartsAt = $promotion->starts_at;
        if ($promotionStartsAt !== null && now()->isBefore($promotionStartsAt)) {
            throw CheckoutException::promotionNotStartedYet();
        }

        /** @var Carbon|null $promotionEndsAt */
        $promotionEndsAt = $promotion->ends_at;
        if ($promotionEndsAt !== null && now()->isAfter($promotionEndsAt)) {
            throw CheckoutException::promotionHasEnded();
        }

        if ($promotion->usage_limit !== null && $promotion->usage_count >= $promotion->usage_limit) {
            throw CheckoutException::promotionUsageLimitExceeded();
        }

        return new CheckoutDiscountContextDto(
            discountTotal: $this->calculateDiscountTotal($promotion->type, $promotion->value, $subtotal),
            coupon: $coupon,
            promotion: $promotion,
        );
    }

    /**
     * Calculate discount amount by promotion type.
     *
     * The promotion value crosses the domain boundary as an exact decimal
     * string (Eloquent `decimal:2` cast) and never as float, so percentage
     * rates and fixed amounts keep full precision through Money arithmetic.
     */
    private function calculateDiscountTotal(PromotionType $type, string $promotionValue, Money $subtotal): Money
    {
        $discount = match ($type) {
            PromotionType::PERCENT => $this->resolvePercentDiscount($promotionValue, $subtotal),
            PromotionType::FIXED => Money::fromDecimal($promotionValue, $subtotal->currency()),
        };

        return $discount->min($subtotal);
    }

    /**
     * Resolve percent discount with domain-level rate validation.
     *
     * The HTTP layer already rejects values above 100, but a domain call
     * without that guard would produce a discount larger than the subtotal;
     * defend the boundary explicitly and surface a typed failure instead.
     */
    private function resolvePercentDiscount(string $rate, Money $subtotal): Money
    {
        if (! is_numeric($rate) || (float) $rate < 0 || (float) $rate > 100) {
            throw CheckoutException::promotionTypeInvalid(
                new \DomainException(sprintf('Percent value must be between 0 and 100, got "%s".', $rate)),
            );
        }

        return $subtotal->percentage($rate);
    }
}
