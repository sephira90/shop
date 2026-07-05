<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Application\Dto;

use App\Models\Order;
use App\Models\Payment;

final readonly class CheckoutPlaceOrderResultDto
{
    public static function fromModels(Order $order, Payment $payment): self
    {
        return new self(
            order: CheckoutOrderResultDto::fromOrder($order),
            payment: CheckoutPaymentResultDto::fromPayment($payment),
        );
    }

    public function __construct(
        public CheckoutOrderResultDto $order,
        public CheckoutPaymentResultDto $payment,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ...$this->order->toArray(),
            'payment' => $this->payment->toArray(),
        ];
    }
}
