<?php

declare(strict_types=1);

namespace App\Application\Checkout\Commands;

use App\Models\Order;
use App\Models\Payment;

final readonly class PlaceCheckoutOrderResult
{
    /**
     * Create result payload for place-order flow.
     */
    public function __construct(
        public Order $order,
        public Payment $payment,
    ) {}
}
