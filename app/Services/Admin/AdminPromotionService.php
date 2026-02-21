<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Coupon;
use App\Models\Promotion;
use Illuminate\Support\Facades\DB;

final class AdminPromotionService
{
    /**
     * Create service instance.
     */
    public function __construct(private readonly PromotionCouponSyncService $promotionCouponSyncService) {}

    /**
     * Create promotion entry.
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): Promotion
    {
        return DB::transaction(function () use ($payload): Promotion {
            $couponPayload = $payload['coupon'] ?? null;
            $couponPayload = is_array($couponPayload) ? $couponPayload : null;
            unset($payload['coupon']);

            if (array_key_exists('code', $payload) && $payload['code'] !== null) {
                $payload['code'] = $this->promotionCouponSyncService->normalizeCode((string) $payload['code']);
            }

            $promotion = Promotion::query()->create($payload);

            $this->promotionCouponSyncService->createPrimaryCouponIfRequired($promotion, $payload, $couponPayload);

            /** @var Promotion $freshPromotion */
            $freshPromotion = $promotion->fresh('coupons');

            return $freshPromotion;
        });
    }

    /**
     * Update promotion entry.
     *
     * @param  array<string, mixed>  $payload
     */
    public function update(Promotion $promotion, array $payload): Promotion
    {
        return DB::transaction(function () use ($promotion, $payload): Promotion {
            if (array_key_exists('code', $payload) && $payload['code'] !== null) {
                $payload['code'] = $this->promotionCouponSyncService->normalizeCode((string) $payload['code']);
            }

            $promotion->update($payload);

            if (array_key_exists('code', $payload) && $payload['code'] !== null) {
                $this->promotionCouponSyncService->syncPrimaryCouponCode($promotion, (string) $payload['code']);
            }

            /** @var Promotion $freshPromotion */
            $freshPromotion = $promotion->fresh('coupons');

            return $freshPromotion;
        });
    }

    /**
     * Delete promotion and related coupons.
     */
    public function delete(Promotion $promotion): void
    {
        $promotion->delete();
    }

    /**
     * Create coupon for existing promotion.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createCoupon(Promotion $promotion, array $payload): Coupon
    {
        return $this->promotionCouponSyncService->createCoupon($promotion, $payload);
    }

    /**
     * Update coupon flags and limits.
     *
     * @param  array<string, mixed>  $payload
     */
    public function updateCoupon(Coupon $coupon, array $payload): Coupon
    {
        return $this->promotionCouponSyncService->updateCoupon($coupon, $payload);
    }
}
