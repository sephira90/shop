<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Application\Admin\Promotions\Commands\CreateAdminPromotionCommand;
use App\Application\Admin\Promotions\Commands\CreateAdminPromotionCouponCommand;
use App\Application\Admin\Promotions\Commands\CreateAdminPromotionCouponHandler;
use App\Application\Admin\Promotions\Commands\CreateAdminPromotionHandler;
use App\Application\Admin\Promotions\Commands\DeleteAdminPromotionCommand;
use App\Application\Admin\Promotions\Commands\DeleteAdminPromotionHandler;
use App\Application\Admin\Promotions\Commands\UpdateAdminPromotionCommand;
use App\Application\Admin\Promotions\Commands\UpdateAdminPromotionCouponCommand;
use App\Application\Admin\Promotions\Commands\UpdateAdminPromotionCouponHandler;
use App\Application\Admin\Promotions\Commands\UpdateAdminPromotionHandler;
use App\Application\Admin\Promotions\Queries\PaginateAdminPromotionsHandler;
use App\Application\Admin\Promotions\Queries\PaginateAdminPromotionsQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponStoreRequest;
use App\Http\Requests\Admin\CouponUpdateRequest;
use App\Http\Requests\Admin\PromotionIndexRequest;
use App\Http\Requests\Admin\PromotionStoreRequest;
use App\Http\Requests\Admin\PromotionUpdateRequest;
use App\Models\Coupon;
use App\Models\Promotion;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class PromotionController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(
        private readonly PaginateAdminPromotionsHandler $paginateAdminPromotionsHandler,
        private readonly CreateAdminPromotionHandler $createAdminPromotionHandler,
        private readonly UpdateAdminPromotionHandler $updateAdminPromotionHandler,
        private readonly DeleteAdminPromotionHandler $deleteAdminPromotionHandler,
        private readonly CreateAdminPromotionCouponHandler $createAdminPromotionCouponHandler,
        private readonly UpdateAdminPromotionCouponHandler $updateAdminPromotionCouponHandler,
    ) {}

    /**
     * List promotions for admin.
     */
    public function index(PromotionIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Promotion::class);

        $promotions = $this->paginateAdminPromotionsHandler->handle(
            new PaginateAdminPromotionsQuery($request->filter())
        );

        return ApiResponse::paginatedWithMeta($promotions->itemsToArray(), $promotions->metaToArray());
    }

    /**
     * Store new promotion.
     */
    public function store(PromotionStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Promotion::class);

        $promotion = $this->createAdminPromotionHandler->handle(
            new CreateAdminPromotionCommand($request->toDto())
        );

        return ApiResponse::data($promotion->toArray(), 201);
    }

    /**
     * Update promotion campaign.
     */
    public function update(PromotionUpdateRequest $request, Promotion $promotion): JsonResponse
    {
        $this->authorize('update', $promotion);

        $promotion = $this->updateAdminPromotionHandler->handle(
            new UpdateAdminPromotionCommand($promotion, $request->toDto())
        );

        return ApiResponse::data($promotion->toArray());
    }

    /**
     * Delete promotion campaign.
     */
    public function destroy(Promotion $promotion): JsonResponse
    {
        $this->authorize('delete', $promotion);

        $this->deleteAdminPromotionHandler->handle(new DeleteAdminPromotionCommand($promotion));

        return ApiResponse::deleted();
    }

    /**
     * Add coupon to existing promotion.
     */
    public function storeCoupon(CouponStoreRequest $request, Promotion $promotion): JsonResponse
    {
        $this->authorize('update', $promotion);

        $coupon = $this->createAdminPromotionCouponHandler->handle(
            new CreateAdminPromotionCouponCommand($promotion, $request->toDto())
        );

        return ApiResponse::data($coupon->toArray(), 201);
    }

    /**
     * Update coupon state.
     */
    public function updateCoupon(CouponUpdateRequest $request, Coupon $coupon): JsonResponse
    {
        $this->authorize('update', $coupon);

        $coupon = $this->updateAdminPromotionCouponHandler->handle(
            new UpdateAdminPromotionCouponCommand($coupon, $request->toDto())
        );

        return ApiResponse::data($coupon->toArray());
    }
}
