<?php

declare(strict_types=1);

namespace App\Domains\Users\Support;

use App\Domains\Users\Application\Dto\AccountOrdersSummaryResultDto;
use App\Domains\Users\Application\Dto\AccountOrderSummaryAggregateDto;
use App\Domains\Users\Application\Dto\AccountOrderSummaryStatusGroupDto;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;

final class AccountOrderSummaryProjector
{
    public function project(AccountOrderSummaryAggregateDto $aggregate): AccountOrdersSummaryResultDto
    {
        $paidOrders = 0;
        $inDeliveryOrders = 0;

        foreach ($aggregate->statusGroups as $statusGroup) {
            if ($this->countsAsPaid($statusGroup)) {
                $paidOrders += $statusGroup->count;
            }

            if ($this->countsAsInDelivery($statusGroup)) {
                $inDeliveryOrders += $statusGroup->count;
            }
        }

        return new AccountOrdersSummaryResultDto(
            totalOrders: $aggregate->totalOrders,
            paidOrders: $paidOrders,
            inDeliveryOrders: $inDeliveryOrders,
            totalSpent: $aggregate->totalSpent,
        );
    }

    private function countsAsPaid(AccountOrderSummaryStatusGroupDto $statusGroup): bool
    {
        return $statusGroup->orderStatus === OrderStatus::PAID->value
            || $statusGroup->paymentStatus === PaymentStatus::CAPTURED->value;
    }

    private function countsAsInDelivery(AccountOrderSummaryStatusGroupDto $statusGroup): bool
    {
        return in_array(
            $statusGroup->shipmentStatus,
            [ShipmentStatus::PACKED->value, ShipmentStatus::SHIPPED->value],
            true,
        );
    }
}
