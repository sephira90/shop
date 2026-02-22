<?php

declare(strict_types=1);

namespace App\Application\Checkout\Queries;

use App\Repositories\OrderRepository;

final class GetMyOrdersSummaryHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly OrderRepository $orderRepository,
    ) {}

    /**
     * Execute account order summary query.
     *
     * @return array{total_orders:int,paid_orders:int,in_delivery_orders:int,total_spent:float}
     */
    public function handle(GetMyOrdersSummaryQuery $query): array
    {
        return $this->orderRepository->summaryForUser($query->user);
    }
}
