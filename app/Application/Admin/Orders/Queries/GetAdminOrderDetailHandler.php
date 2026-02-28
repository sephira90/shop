<?php

declare(strict_types=1);

namespace App\Application\Admin\Orders\Queries;

use App\Application\Admin\Orders\Dto\AdminOrderDetailResultDto;

final class GetAdminOrderDetailHandler
{
    /**
     * Execute admin order detail query.
     */
    public function handle(GetAdminOrderDetailQuery $query): AdminOrderDetailResultDto
    {
        $order = $query->order->load(['items', 'payments', 'shipments', 'user']);

        return AdminOrderDetailResultDto::fromOrder($order);
    }
}
