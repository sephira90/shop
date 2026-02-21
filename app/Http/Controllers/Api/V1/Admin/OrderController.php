<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderIndexRequest;
use App\Http\Requests\Admin\OrderStatusUpdateRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderSummaryResource;
use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Services\Admin\AdminOrderService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly AdminOrderService $adminOrderService,
    ) {}

    /**
     * List orders for admin panel.
     */
    public function index(OrderIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $orders = $this->orderRepository->paginateSummaryForAdmin($request->filter());

        return ApiResponse::paginated(OrderSummaryResource::collection($orders->items()), $orders);
    }

    /**
     * Show one order.
     */
    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return ApiResponse::data(OrderResource::make($order->load(['items', 'payments', 'shipments', 'user'])));
    }

    /**
     * Update order statuses.
     */
    public function updateStatus(OrderStatusUpdateRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        $updated = $this->adminOrderService->updateStatus($order, $request->validated());

        return ApiResponse::data(OrderResource::make($updated));
    }
}
