<?php

declare(strict_types=1);

namespace App\Application\Checkout\Commands;

use App\Models\Payment;
use App\Services\Payment\PaymentService;

final class InitiateCheckoutPaymentHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * Execute checkout payment initiate command.
     */
    public function handle(InitiateCheckoutPaymentCommand $command): Payment
    {
        return $this->paymentService->initiate($command->order, $command->idempotencyKey);
    }
}
