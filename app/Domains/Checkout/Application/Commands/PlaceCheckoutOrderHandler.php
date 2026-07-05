<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Application\Commands;

use App\Domains\Checkout\Application\Dto\CheckoutPlaceOrderResultDto;
use App\Domains\Checkout\Services\CheckoutPlaceOrderOrchestrator;

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
