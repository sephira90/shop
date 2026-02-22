<?php

declare(strict_types=1);

namespace App\Application\Admin\Orders\Commands;

use App\Models\Order;
use App\Services\Admin\AdminOrderService;

final class UpdateAdminOrderStatusHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AdminOrderService $adminOrderService,
    ) {}

    /**
     * Execute admin order status update command.
     */
    public function handle(UpdateAdminOrderStatusCommand $command): Order
    {
        return $this->adminOrderService->updateStatus($command->order, $command->payload);
    }
}
