<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Application\Admin\Promotions\Dto\CreateAdminPromotionCouponInputDto;
use App\Application\Admin\Promotions\Dto\CreateAdminPromotionInputDto;
use App\Application\Admin\Promotions\Dto\UpdateAdminPromotionCouponInputDto;
use App\Application\Admin\Promotions\Dto\UpdateAdminPromotionInputDto;
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
     */
    public function create(CreateAdminPromotionInputDto $input): Promotion
    {
        return DB::transaction(function () use ($input): Promotion {
            $promotionAttributes = $input->toPromotionAttributes();
            if ($input->code !== null) {
                $promotionAttributes['code'] = $this->promotionCouponSyncService->normalizeCode($input->code);
            }

            $promotion = Promotion::query()->create($promotionAttributes);

            $this->promotionCouponSyncService->createPrimaryCouponIfRequired($promotion, $input);

            /** @var Promotion $freshPromotion */
            $freshPromotion = $promotion->fresh('coupons');

            return $freshPromotion;
        });
    }

    /**
     * Update promotion entry.
     */
    public function update(Promotion $promotion, UpdateAdminPromotionInputDto $input): Promotion
    {
        return DB::transaction(function () use ($promotion, $input): Promotion {
            $promotionAttributes = $input->toPromotionAttributes();
            if ($input->hasCode && $input->code !== null) {
                $promotionAttributes['code'] = $this->promotionCouponSyncService->normalizeCode($input->code);
            }

            $promotion->update($promotionAttributes);

            if ($input->hasCode && $input->code !== null) {
                $this->promotionCouponSyncService->syncPrimaryCouponCode($promotion, $input->code);
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
     */
    public function createCoupon(Promotion $promotion, CreateAdminPromotionCouponInputDto $input): Coupon
    {
        return $this->promotionCouponSyncService->createCoupon($promotion, $input);
    }

    /**
     * Update coupon flags and limits.
     */
    public function updateCoupon(Coupon $coupon, UpdateAdminPromotionCouponInputDto $input): Coupon
    {
        return $this->promotionCouponSyncService->updateCoupon($coupon, $input);
    }
}
