<?php

declare(strict_types=1);

namespace App\Application\Account\Orders\Dto;

use App\Models\Payment;

final readonly class AccountOrderPaymentResultDto
{
    public static function fromPayment(Payment $payment): self
    {
        return new self(
            gateway: (string) $payment->gateway,
            transactionId: (string) $payment->transaction_id,
            status: self::nullableStatus($payment->getRawOriginal('status')),
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
     * @return array{gateway:string,transaction_id:string,status:string|null,amount:float}
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

    private static function nullableStatus(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
