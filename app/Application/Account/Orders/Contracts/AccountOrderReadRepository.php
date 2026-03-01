<?php

declare(strict_types=1);

namespace App\Application\Account\Orders\Contracts;

use App\Application\Account\Orders\Dto\AccountOrderListFilterDto;
use App\Application\Account\Orders\Dto\AccountOrderSummaryAggregateDto;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AccountOrderReadRepository
{
    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateSummariesForUser(
        User $user,
        AccountOrderListFilterDto $filter,
    ): LengthAwarePaginator;

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateLegacyDetailsForUser(
        User $user,
        AccountOrderListFilterDto $filter,
    ): LengthAwarePaginator;

    public function findDetailForUser(User $user, string $orderId): ?Order;

    public function getSummaryAggregateForUser(User $user): AccountOrderSummaryAggregateDto;
}
