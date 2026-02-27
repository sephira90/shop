<?php

declare(strict_types=1);

namespace App\Application\Checkout\Dto;

use App\Models\Order;
use App\Models\Payment;

final readonly class CheckoutPlaceOrderResultDto
{
    public static function fromModels(Order $order, Payment $payment): self
    {
        return new self(
            order: $order,
            payment: CheckoutPaymentResultDto::fromPayment($payment),
        );
    }

    public function __construct(
        public Order $order,
        public CheckoutPaymentResultDto $payment,
    ) {}

    /**
     * @param  array<string, mixed>  $orderPayload
     * @return array<string, mixed>
     */
    public function toArray(array $orderPayload): array
    {
        return [
            ...$orderPayload,
            'payment' => $this->payment->toArray(),
        ];
    }
}
