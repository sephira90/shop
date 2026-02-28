<?php

declare(strict_types=1);

namespace App\Application\Checkout\Dto;

use App\Models\Payment;

final readonly class CheckoutOrderPaymentResultDto
{
    public static function fromPayment(Payment $payment): self
    {
        $status = $payment->getRawOriginal('status');

        return new self(
            gateway: (string) $payment->gateway,
            transactionId: (string) $payment->transaction_id,
            status: is_string($status) && trim($status) !== '' ? $status : null,
            amount: (float) $payment->amount,
        );
    }

    public function __construct(
        public string $gateway,
        public string $transactionId,
        public ?string $status,
        public float $amount,
    ) {}

    /**
     * @return array{
     *     gateway:string,
     *     transaction_id:string,
     *     status:string|null,
     *     amount:float
     * }
     */
    public function toArray(): array
    {
        return [
            'gateway' => $this->gateway,
            'transaction_id' => $this->transactionId,
            'status' => $this->status,
            'amount' => $this->amount,
        ];
    }
}
