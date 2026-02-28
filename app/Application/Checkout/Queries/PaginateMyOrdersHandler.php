<?php

declare(strict_types=1);

namespace App\Application\Checkout\Queries;

use App\Application\Checkout\Dto\CheckoutOrderPaginatedResultDto;
use App\Repositories\OrderRepository;

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
     */
    public function handle(PaginateMyOrdersQuery $query): CheckoutOrderPaginatedResultDto
    {
        return CheckoutOrderPaginatedResultDto::fromPaginator(
            $this->orderRepository->paginateForUser($query->user, $query->filter)
        );
    }
}
