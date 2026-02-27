<?php

declare(strict_types=1);

namespace App\Application\Admin\Orders\Commands;

use App\Application\Admin\Orders\Dto\UpdateAdminOrderStatusInputDto;
use App\Models\Order;

final readonly class UpdateAdminOrderStatusCommand
{
    public function __construct(
        public Order $order,
        public UpdateAdminOrderStatusInputDto $input,
    ) {}
}
