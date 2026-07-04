<?php

declare(strict_types=1);

namespace App\Support\Orders\Dto;

final readonly class OrdersReconcileOptionsDto
{
    public function __construct(
        public int $stuckShipmentMinutes,
        public int $stalePendingPaymentMinutes,
        public int $failedJobsThreshold,
        public bool $json,
    ) {}
}
