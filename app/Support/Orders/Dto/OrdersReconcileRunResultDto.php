<?php

declare(strict_types=1);

namespace App\Support\Orders\Dto;

/**
 * Aggregate result of an app:orders-reconcile run. Each detector contributes
 * a list of findings; the runner is orchestration-only and never mutates
 * state. The result is considered clean only when every detector list is
 * empty.
 */
final readonly class OrdersReconcileRunResultDto
{
    /**
     * @param  list<OrdersReconcileFindingDto>  $stuckShipments
     * @param  list<OrdersReconcileFindingDto>  $stalePendingPayments
     * @param  list<OrdersReconcileFindingDto>  $failedJobs
     */
    public function __construct(
        public OrdersReconcileOptionsDto $options,
        public array $stuckShipments,
        public array $stalePendingPayments,
        public array $failedJobs,
    ) {}

    public function isClean(): bool
    {
        return $this->stuckShipments === []
            && $this->stalePendingPayments === []
            && $this->failedJobs === [];
    }
}
