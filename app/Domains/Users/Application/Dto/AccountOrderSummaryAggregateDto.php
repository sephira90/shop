<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Dto;

final readonly class AccountOrderSummaryAggregateDto
{
    /**
     * @param  list<AccountOrderSummaryStatusGroupDto>  $statusGroups
     */
    public function __construct(
        public int $totalOrders,
        public float $totalSpent,
        public array $statusGroups,
    ) {}
}
