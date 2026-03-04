<?php

declare(strict_types=1);

namespace App\Events;

use App\Domain\Order\StatusTransitionSource;
use App\Enums\ShipmentStatus;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create event instance.
     */
    public function __construct(
        public readonly string $orderId,
        public readonly string $shipmentId,
        public readonly ShipmentStatus $previousStatus,
        public readonly ShipmentStatus $currentStatus,
        public readonly StatusTransitionSource $source,
    ) {}
}
