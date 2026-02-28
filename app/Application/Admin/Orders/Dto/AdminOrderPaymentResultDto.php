<?php

declare(strict_types=1);

namespace App\Application\Admin\Orders\Dto;

use App\Models\Payment;

final readonly class AdminOrderPaymentResultDto
{
    public static function fromPayment(Payment $payment): self
    {
        return new self(
            gateway: (string) $payment->gateway,
            transactionId: (string) $payment->transaction_id,
            status: self::resolveStatus($payment),
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

    private static function resolveStatus(Payment $payment): ?string
    {
        $status = $payment->getRawOriginal('status');

        if (! is_string($status) || trim($status) === '') {
            return null;
        }

        return $status;
    }
}
