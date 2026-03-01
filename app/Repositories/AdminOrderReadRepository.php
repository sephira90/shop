<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Application\Admin\Orders\Dto\AdminOrderListFilterDto;
use App\Models\Order;
use App\Repositories\Concerns\AppliesOrderSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class AdminOrderReadRepository
{
    use AppliesOrderSearch;

    /**
     * Get summary list of orders for admin area.
     *
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateSummaryForAdmin(AdminOrderListFilterDto $filter): LengthAwarePaginator
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

        $this->applyOrderSearch($query, $filter->search);

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
