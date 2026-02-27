<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Application\Admin\Promotions\Dto\CreateAdminPromotionCouponInputDto;
use App\Application\Admin\Promotions\Dto\CreateAdminPromotionInputDto;
use App\Application\Admin\Promotions\Dto\UpdateAdminPromotionCouponInputDto;
use App\Models\Coupon;
use App\Models\Promotion;
use Illuminate\Support\Str;

final class PromotionCouponSyncService
{
    /**
     * Create coupon for existing promotion.
     */
    public function createCoupon(Promotion $promotion, CreateAdminPromotionCouponInputDto $input): Coupon
    {
        $coupon = Coupon::query()->create([
            'promotion_id' => $promotion->id,
            'code' => $this->normalizeCode($input->requiredCode()),
            'is_active' => $input->isActive,
            'max_redemptions' => $input->maxRedemptions,
            'expires_at' => $input->expiresAt,
        ]);

        /** @var Coupon $freshCoupon */
        $freshCoupon = $coupon->fresh();

        return $freshCoupon;
    }

    /**
     * Update coupon flags and limits.
     */
    public function updateCoupon(Coupon $coupon, UpdateAdminPromotionCouponInputDto $input): Coupon
    {
        $updates = [];

        if ($input->hasIsActive) {
            $updates['is_active'] = (bool) $input->isActive;
        }
        if ($input->hasMaxRedemptions) {
            $updates['max_redemptions'] = $input->maxRedemptions;
        }
        if ($input->hasExpiresAt) {
            $updates['expires_at'] = $input->expiresAt;
        }

        if ($updates !== []) {
            $coupon->update($updates);
        }

        /** @var Coupon $freshCoupon */
        $freshCoupon = $coupon->fresh();

        return $freshCoupon;
    }

    /**
     * Create initial coupon from promotion DTO.
     */
    public function createPrimaryCouponIfRequired(Promotion $promotion, CreateAdminPromotionInputDto $input): void
    {
        $primaryCode = null;
        $coupon = $input->coupon;

        if ($coupon !== null && $coupon->hasCode && $coupon->code !== null) {
            $primaryCode = $coupon->code;
        } elseif ($input->code !== null) {
            // Backward-compatible path: promotion code acts as primary coupon code.
            $primaryCode = $input->code;
        }

        if ($primaryCode === null) {
            return;
        }

        Coupon::query()->create([
            'promotion_id' => $promotion->id,
            'code' => $this->normalizeCode($primaryCode),
            'is_active' => $coupon !== null ? $coupon->isActive : true,
            'max_redemptions' => $coupon?->maxRedemptions,
            'expires_at' => $coupon?->expiresAt,
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
