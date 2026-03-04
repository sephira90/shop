<?php

declare(strict_types=1);

namespace App\Application\Admin\Orders\Contracts;

use App\Application\Admin\Orders\Dto\AdminOrderListFilterDto;
use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

interface AdminOrderReadRepository
{
    /**
     * Get summary list of orders for admin area.
     *
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginateSummaryForAdmin(AdminOrderListFilterDto $filter): LengthAwarePaginator;
}
