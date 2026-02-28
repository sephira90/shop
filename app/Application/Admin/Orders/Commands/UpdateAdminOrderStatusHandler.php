<?php

declare(strict_types=1);

namespace App\Application\Admin\Orders\Commands;

use App\Application\Admin\Orders\Dto\AdminOrderDetailResultDto;
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
    public function handle(UpdateAdminOrderStatusCommand $command): AdminOrderDetailResultDto
    {
        $order = $this->adminOrderService->updateStatus($command->order, $command->input);

        return AdminOrderDetailResultDto::fromOrder($order);
    }
}
