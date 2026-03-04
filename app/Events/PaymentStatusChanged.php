<?php

declare(strict_types=1);

namespace App\Events;

use App\Domain\Order\StatusTransitionSource;
use App\Enums\PaymentStatus;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create event instance.
     */
    public function __construct(
        public readonly string $orderId,
        public readonly string $paymentId,
        public readonly PaymentStatus $previousStatus,
        public readonly PaymentStatus $currentStatus,
        public readonly StatusTransitionSource $source,
    ) {}
}
