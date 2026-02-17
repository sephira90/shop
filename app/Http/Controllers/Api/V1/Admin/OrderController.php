<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderStatusUpdateRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Services\Admin\AdminOrderService;
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
    public function index(): JsonResponse
    {
        $orders = $this->orderRepository->paginateForAdmin(30);

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Show one order.
     */
    public function show(Order $order): JsonResponse
    {
        return response()->json([
            'data' => OrderResource::make($order->load(['items', 'payments', 'shipments', 'user'])),
        ]);
    }

    /**
     * Update order statuses.
     */
    public function updateStatus(OrderStatusUpdateRequest $request, Order $order): JsonResponse
    {
        $updated = $this->adminOrderService->updateStatus($order, $request->validated());

        return response()->json([
            'data' => OrderResource::make($updated),
        ]);
    }
}
