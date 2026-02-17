<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromotionStoreRequest;
use App\Models\Promotion;
use App\Services\Admin\AdminPromotionService;
use Illuminate\Http\JsonResponse;

class PromotionController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(private readonly AdminPromotionService $adminPromotionService) {}

    /**
     * List promotions for admin.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Promotion::query()->latest('id')->paginate(30),
        ]);
    }

    /**
     * Store new promotion.
     */
    public function store(PromotionStoreRequest $request): JsonResponse
    {
        $promotion = $this->adminPromotionService->create($request->validated());

        return response()->json([
            'data' => $promotion,
        ], 201);
    }
}
