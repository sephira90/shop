<?php

declare(strict_types=1);

namespace App\Services\Shipping;

use App\Enums\ShipmentStatus;

final class ShipmentStatusTransitionPolicy
{
    /**
     * Validate shipment status transition matrix.
     */
    public function canTransition(ShipmentStatus $from, ShipmentStatus $to): bool
    {
        return match ($from) {
            ShipmentStatus::PENDING => in_array($to, [
                ShipmentStatus::PENDING,
                ShipmentStatus::PACKED,
                ShipmentStatus::SHIPPED,
                ShipmentStatus::DELIVERED,
            ], true),
            ShipmentStatus::PACKED => in_array($to, [
                ShipmentStatus::PACKED,
                ShipmentStatus::SHIPPED,
                ShipmentStatus::DELIVERED,
                ShipmentStatus::RETURNED,
            ], true),
            ShipmentStatus::SHIPPED => in_array($to, [
                ShipmentStatus::SHIPPED,
                ShipmentStatus::DELIVERED,
                ShipmentStatus::RETURNED,
            ], true),
            ShipmentStatus::DELIVERED => in_array($to, [
                ShipmentStatus::DELIVERED,
                ShipmentStatus::RETURNED,
            ], true),
            ShipmentStatus::RETURNED => $to === ShipmentStatus::RETURNED,
        };
    }
}
