<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Application\Commands;

use App\Domains\Checkout\Application\Dto\CheckoutPaymentResultDto;
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
    public function handle(InitiateCheckoutPaymentCommand $command): CheckoutPaymentResultDto
    {
        return CheckoutPaymentResultDto::fromPayment(
            $this->paymentService->initiate($command->order, $command->idempotencyKey),
        );
    }
}
