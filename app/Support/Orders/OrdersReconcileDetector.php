<?php

declare(strict_types=1);

namespace App\Support\Orders;

use App\Support\Orders\Dto\OrdersReconcileFindingDto;
use App\Support\Orders\Dto\OrdersReconcileOptionsDto;

/**
 * Reconciles a single class of order lifecycle stuck-state against the
 * resolved options. Implementations are read-only and deterministic.
 */
interface OrdersReconcileDetector
{
    /**
     * @return list<OrdersReconcileFindingDto>
     */
    public function detect(OrdersReconcileOptionsDto $options): array;
}
