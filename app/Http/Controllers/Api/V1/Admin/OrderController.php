<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Application\Admin\Orders\Commands\UpdateAdminOrderStatusCommand;
use App\Application\Admin\Orders\Commands\UpdateAdminOrderStatusHandler;
use App\Application\Admin\Orders\Queries\GetAdminOrderDetailHandler;
use App\Application\Admin\Orders\Queries\GetAdminOrderDetailQuery;
use App\Application\Admin\Orders\Queries\PaginateAdminOrdersHandler;
use App\Application\Admin\Orders\Queries\PaginateAdminOrdersQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderIndexRequest;
use App\Http\Requests\Admin\OrderStatusUpdateRequest;
use App\Models\Order;
use App\Support\Api\ApiResponse;
use DomainException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(
        private readonly PaginateAdminOrdersHandler $paginateAdminOrdersHandler,
        private readonly GetAdminOrderDetailHandler $getAdminOrderDetailHandler,
        private readonly UpdateAdminOrderStatusHandler $updateAdminOrderStatusHandler,
    ) {}

    /**
     * List orders for admin panel.
     */
    public function index(OrderIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $orders = $this->paginateAdminOrdersHandler->handle(
            new PaginateAdminOrdersQuery($request->filter())
        );

        return ApiResponse::paginatedWithMeta($orders->itemsToArray(), $orders->metaToArray());
    }

    /**
     * Show one order.
     */
    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $detail = $this->getAdminOrderDetailHandler->handle(new GetAdminOrderDetailQuery($order));

        return ApiResponse::data($detail->toArray());
    }

    /**
     * Update order statuses.
     */
    public function updateStatus(OrderStatusUpdateRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        try {
            $updated = $this->updateAdminOrderStatusHandler->handle(
                new UpdateAdminOrderStatusCommand($order, $request->toDto())
            );
        } catch (DomainException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return ApiResponse::data($updated->toArray());
    }
}
