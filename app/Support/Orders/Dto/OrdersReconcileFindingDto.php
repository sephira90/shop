<?php

declare(strict_types=1);

namespace App\Support\Orders\Dto;

/**
 * One observed stuck-state instance surfaced by a reconciliation detector.
 */
final readonly class OrdersReconcileFindingDto
{
    public function __construct(
        public string $kind,
        public ?string $orderId = null,
        public ?string $orderNumber = null,
        public ?int $ageMinutes = null,
        public ?int $count = null,
    ) {}
}
