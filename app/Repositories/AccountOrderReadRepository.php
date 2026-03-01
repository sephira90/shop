<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Application\Account\Orders\Contracts\AccountOrderReadRepository as AccountOrderReadRepositoryContract;
use App\Application\Account\Orders\Dto\AccountOrderListFilterDto;
use App\Application\Account\Orders\Dto\AccountOrderSummaryAggregateDto;
use App\Application\Account\Orders\Dto\AccountOrderSummaryStatusGroupDto;
use App\Models\Order;
use App\Models\User;
use App\Repositories\Concerns\AppliesOrderSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class AccountOrderReadRepository implements AccountOrderReadRepositoryContract
{
    use AppliesOrderSearch;

    public function paginateSummariesForUser(
        User $user,
        AccountOrderListFilterDto $filter,
    ): LengthAwarePaginator {
        /** @var LengthAwarePaginator<int, Order> $paginator */
        $paginator = $this->baseUserOrderQuery($user, $filter)
            ->select([
                'id',
                'order_number',
                'email',
                'status',
                'payment_status',
                'shipment_status',
                'currency',
                'total',
                'placed_at',
                'created_at',
            ])
            ->paginate($filter->perPage, ['*'], 'page', $filter->page);

        return $paginator;
    }

    public function paginateLegacyDetailsForUser(
        User $user,
        AccountOrderListFilterDto $filter,
    ): LengthAwarePaginator {
        /** @var LengthAwarePaginator<int, Order> $paginator */
        $paginator = $this->baseUserOrderQuery($user, $filter)
            ->with(['items', 'payments', 'shipments'])
            ->paginate($filter->perPage, ['*'], 'page', $filter->page);

        return $paginator;
    }

    public function findDetailForUser(User $user, string $orderId): ?Order
    {
        return Order::query()
            ->whereKey($orderId)
            ->where('user_id', $user->id)
            ->with(['items', 'payments', 'shipments'])
            ->first();
    }

    public function getSummaryAggregateForUser(User $user): AccountOrderSummaryAggregateDto
    {
        $base = Order::query()->where('user_id', $user->id);

        return new AccountOrderSummaryAggregateDto(
            totalOrders: (clone $base)->count(),
            totalSpent: (float) (clone $base)->sum('total'),
            statusGroups: $this->fetchSummaryStatusGroups($base),
        );
    }

    /**
     * @return Builder<Order>
     */
    private function baseUserOrderQuery(User $user, AccountOrderListFilterDto $filter): Builder
    {
        /** @var Builder<Order> $query */
        $query = Order::query()
            ->where('user_id', $user->id)
            ->latest('placed_at')
            ->latest('created_at');

        $this->applyOrderSearch($query, $filter->search);

        if ($filter->orderStatus !== null) {
            $query->where('status', $filter->orderStatus->value);
        }

        return $query;
    }

    /**
     * @param  Builder<Order>  $baseQuery
     * @return list<AccountOrderSummaryStatusGroupDto>
     */
    private function fetchSummaryStatusGroups(Builder $baseQuery): array
    {
        /** @var Collection<int, Order> $rows */
        $rows = (clone $baseQuery)
            ->select([
                'status',
                'payment_status',
                'shipment_status',
            ])
            ->selectRaw('COUNT(*) as aggregate_count')
            ->groupBy('status', 'payment_status', 'shipment_status')
            ->get();

        $groups = [];

        foreach ($rows as $row) {
            $groups[] = AccountOrderSummaryStatusGroupDto::fromModel($row);
        }

        return $groups;
    }
}
