<?php

declare(strict_types=1);

namespace App\Application\Admin\Orders\Queries;

use App\Models\Order;

final readonly class GetAdminOrderDetailQuery
{
    /**
     * Create query payload for admin order detail.
     */
    public function __construct(
        public Order $order,
    ) {}
}
