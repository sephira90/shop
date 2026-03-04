<?php

declare(strict_types=1);

namespace App\Application\Checkout\Commands;

use App\Application\Checkout\Dto\CheckoutPlaceOrderResultDto;
use App\Services\Checkout\CheckoutPlaceOrderOrchestrator;

final class PlaceCheckoutOrderHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly CheckoutPlaceOrderOrchestrator $checkoutPlaceOrderOrchestrator,
    ) {}

    /**
     * Execute place-order flow and return order + payment.
     */
    public function handle(PlaceCheckoutOrderCommand $command): CheckoutPlaceOrderResultDto
    {
        return $this->checkoutPlaceOrderOrchestrator->place(
            $command->input,
            $command->idempotencyKey,
            $command->user,
        );
    }
}
