<?php

declare(strict_types=1);

namespace App\Application\Admin\Orders\Commands;

use App\Models\Order;

final readonly class UpdateAdminOrderStatusCommand
{
    /**
     * Create command payload for admin order status update.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public Order $order,
        public array $payload,
    ) {}
}
