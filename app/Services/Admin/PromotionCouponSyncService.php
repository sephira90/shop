<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Coupon;
use App\Models\Promotion;
use Illuminate\Support\Str;

final class PromotionCouponSyncService
{
    /**
     * Create coupon for existing promotion.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createCoupon(Promotion $promotion, array $payload): Coupon
    {
        $coupon = Coupon::query()->create([
            'promotion_id' => $promotion->id,
            'code' => $this->normalizeCode((string) $payload['code']),
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'max_redemptions' => $payload['max_redemptions'] ?? null,
            'expires_at' => $payload['expires_at'] ?? null,
        ]);

        /** @var Coupon $freshCoupon */
        $freshCoupon = $coupon->fresh();

        return $freshCoupon;
    }

    /**
     * Update coupon flags and limits.
     *
     * @param  array<string, mixed>  $payload
     */
    public function updateCoupon(Coupon $coupon, array $payload): Coupon
    {
        $coupon->update([
            'is_active' => array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : $coupon->is_active,
            'max_redemptions' => array_key_exists('max_redemptions', $payload) ? $payload['max_redemptions'] : $coupon->max_redemptions,
            'expires_at' => array_key_exists('expires_at', $payload) ? $payload['expires_at'] : $coupon->expires_at,
        ]);

        /** @var Coupon $freshCoupon */
        $freshCoupon = $coupon->fresh();

        return $freshCoupon;
    }

    /**
     * Create initial coupon from payload.
     *
     * @param  array<string, mixed>  $promotionPayload
     * @param  array<string, mixed>|null  $couponPayload
     */
    public function createPrimaryCouponIfRequired(Promotion $promotion, array $promotionPayload, ?array $couponPayload): void
    {
        $primaryCode = '';

        if ($couponPayload !== null && ! empty($couponPayload['code'])) {
            $primaryCode = (string) $couponPayload['code'];
        } elseif (! empty($promotionPayload['code'])) {
            // Backward-compatible path: promotion code acts as primary coupon code.
            $primaryCode = (string) $promotionPayload['code'];
        }

        if ($primaryCode === '') {
            return;
        }

        Coupon::query()->create([
            'promotion_id' => $promotion->id,
            'code' => $this->normalizeCode($primaryCode),
            'is_active' => (bool) ($couponPayload['is_active'] ?? true),
            'max_redemptions' => $couponPayload['max_redemptions'] ?? null,
            'expires_at' => $couponPayload['expires_at'] ?? null,
        ]);
    }

    /**
     * Ensure promotion code is mirrored to one coupon if code was changed.
     */
    public function syncPrimaryCouponCode(Promotion $promotion, string $code): void
    {
        $normalizedCode = $this->normalizeCode($code);

        $existingMatch = $promotion->coupons()
            ->where('code', $normalizedCode)
            ->first();

        if ($existingMatch instanceof Coupon) {
            return;
        }

        $firstCoupon = $promotion->coupons()->oldest('id')->first();

        if ($firstCoupon instanceof Coupon) {
            $firstCoupon->update(['code' => $normalizedCode]);

            return;
        }

        Coupon::query()->create([
            'promotion_id' => $promotion->id,
            'code' => $normalizedCode,
            'is_active' => (bool) $promotion->is_active,
            'max_redemptions' => null,
            'expires_at' => null,
        ]);
    }

    /**
     * Normalize coupon/promotion code.
     */
    public function normalizeCode(string $code): string
    {
        return Str::upper(trim($code));
    }
}
