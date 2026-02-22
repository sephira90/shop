<?php

declare(strict_types=1);

namespace App\Application\Admin\Orders\Queries;

use App\Models\Order;

final class GetAdminOrderDetailHandler
{
    /**
     * Execute admin order detail query.
     */
    public function handle(GetAdminOrderDetailQuery $query): Order
    {
        return $query->order->load(['items', 'payments', 'shipments', 'user']);
    }
}
