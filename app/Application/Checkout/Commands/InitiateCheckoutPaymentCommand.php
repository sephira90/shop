<?php

declare(strict_types=1);

namespace App\Application\Checkout\Commands;

use App\Models\Order;

final readonly class InitiateCheckoutPaymentCommand
{
    /**
     * Create command payload for checkout payment initiate flow.
     */
    public function __construct(
        public Order $order,
        public string $idempotencyKey,
    ) {}
}
