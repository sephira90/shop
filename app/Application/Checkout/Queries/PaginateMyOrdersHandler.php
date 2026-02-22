<?php

declare(strict_types=1);

namespace App\Application\Checkout\Queries;

use App\Models\Order;
use App\Repositories\OrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PaginateMyOrdersHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly OrderRepository $orderRepository,
    ) {}

    /**
     * Execute account order list query.
     *
     * @return LengthAwarePaginator<int, Order>
     */
    public function handle(PaginateMyOrdersQuery $query): LengthAwarePaginator
    {
        return $this->orderRepository->paginateForUser($query->user, $query->filter);
    }
}
