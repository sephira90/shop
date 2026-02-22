<?php

declare(strict_types=1);

namespace App\Application\Admin\Orders\Queries;

use App\Models\Order;
use App\Repositories\OrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PaginateAdminOrdersHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly OrderRepository $orderRepository,
    ) {}

    /**
     * Execute admin orders list query.
     *
     * @return LengthAwarePaginator<int, Order>
     */
    public function handle(PaginateAdminOrdersQuery $query): LengthAwarePaginator
    {
        return $this->orderRepository->paginateSummaryForAdmin($query->filter);
    }
}
