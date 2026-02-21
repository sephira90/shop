<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponStoreRequest;
use App\Http\Requests\Admin\CouponUpdateRequest;
use App\Http\Requests\Admin\PromotionIndexRequest;
use App\Http\Requests\Admin\PromotionStoreRequest;
use App\Http\Requests\Admin\PromotionUpdateRequest;
use App\Http\Resources\PromotionResource;
use App\Models\Coupon;
use App\Models\Promotion;
use App\Repositories\PromotionRepository;
use App\Services\Admin\AdminPromotionService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class PromotionController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(
        private readonly PromotionRepository $promotionRepository,
        private readonly AdminPromotionService $adminPromotionService,
    ) {}

    /**
     * List promotions for admin.
     */
    public function index(PromotionIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Promotion::class);

        $promotions = $this->promotionRepository->paginateForAdmin($request->filter());

        return ApiResponse::paginated(PromotionResource::collection($promotions->items()), $promotions);
    }

    /**
     * Store new promotion.
     */
    public function store(PromotionStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Promotion::class);

        $promotion = $this->adminPromotionService->create($request->validated());

        return ApiResponse::data(PromotionResource::make($promotion), 201);
    }

    /**
     * Update promotion campaign.
     */
    public function update(PromotionUpdateRequest $request, Promotion $promotion): JsonResponse
    {
        $this->authorize('update', $promotion);

        $promotion = $this->adminPromotionService->update($promotion, $request->validated());

        return ApiResponse::data(PromotionResource::make($promotion));
    }

    /**
     * Delete promotion campaign.
     */
    public function destroy(Promotion $promotion): JsonResponse
    {
        $this->authorize('delete', $promotion);

        $this->adminPromotionService->delete($promotion);

        return ApiResponse::deleted();
    }

    /**
     * Add coupon to existing promotion.
     */
    public function storeCoupon(CouponStoreRequest $request, Promotion $promotion): JsonResponse
    {
        $this->authorize('update', $promotion);

        $coupon = $this->adminPromotionService->createCoupon($promotion, $request->validated());

        return ApiResponse::data($coupon, 201);
    }

    /**
     * Update coupon state.
     */
    public function updateCoupon(CouponUpdateRequest $request, Coupon $coupon): JsonResponse
    {
        $this->authorize('update', $coupon);

        $coupon = $this->adminPromotionService->updateCoupon($coupon, $request->validated());

        return ApiResponse::data($coupon);
    }
}
