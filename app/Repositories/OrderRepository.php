<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Filters\Account\AccountOrderListFilter;
use App\Filters\Admin\AdminOrderListFilter;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class OrderRepository
{
    /**
     * Get user orders with eager loading.
     *
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateForUser(User $user, AccountOrderListFilter $filter): LengthAwarePaginator
    {
        $query = Order::query()
            ->with(['items'])
            ->where('user_id', $user->id)
            ->latest('created_at');

        if ($filter->search !== null) {
            $like = '%'.$filter->search.'%';

            $query->where(static function (Builder $builder) use ($like): void {
                $builder
                    ->where('order_number', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        if ($filter->orderStatus !== null) {
            $query->where('status', $filter->orderStatus->value);
        }

        return $query->paginate($filter->perPage, ['*'], 'page', $filter->page);
    }

    /**
     * Build account-level order metrics for profile dashboard.
     *
     * @return array{total_orders:int,paid_orders:int,in_delivery_orders:int,total_spent:float}
     */
    public function summaryForUser(User $user): array
    {
        $base = Order::query()->where('user_id', $user->id);

        $totalOrders = (clone $base)->count();
        $paidOrders = (clone $base)
            ->where(static function (Builder $builder): void {
                $builder
                    ->where('status', 'paid')
                    ->orWhere('payment_status', 'captured');
            })
            ->count();
        $inDeliveryOrders = (clone $base)
            ->whereIn('shipment_status', ['packed', 'shipped'])
            ->count();
        $totalSpent = (float) (clone $base)->sum('total');

        return [
            'total_orders' => $totalOrders,
            'paid_orders' => $paidOrders,
            'in_delivery_orders' => $inDeliveryOrders,
            'total_spent' => $totalSpent,
        ];
    }

    /**
     * Get summary list of orders for admin area.
     *
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateSummaryForAdmin(AdminOrderListFilter $filter): LengthAwarePaginator
    {
        $query = Order::query()
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
            ]);

        if ($filter->search !== null) {
            $like = '%'.$filter->search.'%';

            $query->where(static function (Builder $builder) use ($like): void {
                $builder
                    ->where('order_number', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        if ($filter->orderStatus !== null) {
            $query->where('status', $filter->orderStatus->value);
        }

        if ($filter->paymentStatus !== null) {
            $query->where('payment_status', $filter->paymentStatus->value);
        }

        if ($filter->shipmentStatus !== null) {
            $query->where('shipment_status', $filter->shipmentStatus->value);
        }

        return $query
            ->latest('created_at')
            ->paginate($filter->perPage, ['*'], 'page', $filter->page);
    }
}
