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
            throw CheckoutException::promotionTypeInvalid($exception);
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
