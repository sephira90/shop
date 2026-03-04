<?php

declare(strict_types=1);

namespace App\Events;

use App\Domain\Order\StatusTransitionSource;
use App\Enums\OrderStatus;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create event instance.
     */
    public function __construct(
        public readonly string $orderId,
        public readonly OrderStatus $previousStatus,
        public readonly OrderStatus $currentStatus,
        public readonly StatusTransitionSource $source,
    ) {}
}
