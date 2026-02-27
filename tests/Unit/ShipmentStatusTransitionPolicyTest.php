<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ShipmentStatus;
use App\Services\Shipping\ShipmentStatusTransitionPolicy;
use PHPUnit\Framework\TestCase;

class ShipmentStatusTransitionPolicyTest extends TestCase
{
    /**
     * Ensure shipment transition matrix is deterministic.
     */
    public function test_shipment_status_transition_matrix_is_stable(): void
    {
        $policy = new ShipmentStatusTransitionPolicy;

        $allowedTransitions = [
            ShipmentStatus::PENDING->value => [
                ShipmentStatus::PENDING,
                ShipmentStatus::PACKED,
                ShipmentStatus::SHIPPED,
                ShipmentStatus::DELIVERED,
            ],
            ShipmentStatus::PACKED->value => [
                ShipmentStatus::PACKED,
                ShipmentStatus::SHIPPED,
                ShipmentStatus::DELIVERED,
                ShipmentStatus::RETURNED,
            ],
            ShipmentStatus::SHIPPED->value => [
                ShipmentStatus::SHIPPED,
                ShipmentStatus::DELIVERED,
                ShipmentStatus::RETURNED,
            ],
            ShipmentStatus::DELIVERED->value => [
                ShipmentStatus::DELIVERED,
                ShipmentStatus::RETURNED,
            ],
            ShipmentStatus::RETURNED->value => [
                ShipmentStatus::RETURNED,
            ],
        ];

        foreach (ShipmentStatus::cases() as $from) {
            foreach (ShipmentStatus::cases() as $to) {
                $expected = in_array($to, $allowedTransitions[$from->value], true);

                self::assertSame(
                    $expected,
                    $policy->canTransition($from, $to),
                    sprintf('Unexpected shipment transition "%s" -> "%s".', $from->value, $to->value),
                );
            }
        }
    }
}
