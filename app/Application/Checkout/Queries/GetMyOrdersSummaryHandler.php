<?php

declare(strict_types=1);

namespace App\Application\Checkout\Queries;

use App\Application\Checkout\Dto\MyOrdersSummaryResultDto;
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
     */
    public function handle(GetMyOrdersSummaryQuery $query): MyOrdersSummaryResultDto
    {
        return MyOrdersSummaryResultDto::fromSummaryArray(
            $this->orderRepository->summaryForUser($query->user),
        );
    }
}
