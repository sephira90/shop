<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Dto;

final readonly class AccountOrdersSummaryResultDto
{
    public function __construct(
        public int $totalOrders,
        public int $paidOrders,
        public int $inDeliveryOrders,
        public float $totalSpent,
    ) {}

    /**
     * @param  array{total_orders:int,paid_orders:int,in_delivery_orders:int,total_spent:float|int}  $summary
     */
    public static function fromSummaryArray(array $summary): self
    {
        return new self(
            totalOrders: $summary['total_orders'],
            paidOrders: $summary['paid_orders'],
            inDeliveryOrders: $summary['in_delivery_orders'],
            totalSpent: (float) $summary['total_spent'],
        );
    }

    /**
     * @return array{total_orders:int,paid_orders:int,in_delivery_orders:int,total_spent:float}
     */
    public function toArray(): array
    {
        return [
            'total_orders' => $this->totalOrders,
            'paid_orders' => $this->paidOrders,
            'in_delivery_orders' => $this->inDeliveryOrders,
            'total_spent' => $this->totalSpent,
        ];
    }
}
