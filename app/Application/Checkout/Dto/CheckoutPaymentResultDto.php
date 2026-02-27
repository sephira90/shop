<?php

declare(strict_types=1);

namespace App\Application\Checkout\Dto;

use App\Models\Payment;

final readonly class CheckoutPaymentResultDto
{
    public static function fromPayment(Payment $payment): self
    {
        $statusRaw = $payment->getRawOriginal('status');
        $status = is_string($statusRaw) && trim($statusRaw) !== '' ? $statusRaw : null;
        $rawPayload = $payment->getRawOriginal('payload');
        $paymentPayload = [];

        if (is_array($rawPayload)) {
            $paymentPayload = $rawPayload;
        } elseif (is_string($rawPayload) && $rawPayload !== '') {
            $decoded = json_decode($rawPayload, true);
            if (is_array($decoded)) {
                $paymentPayload = $decoded;
            }
        }

        return new self(
            paymentId: $payment->id,
            transactionId: $payment->transaction_id,
            status: $status,
            paymentPayload: $paymentPayload,
        );
    }

    /**
     * @param  array<string, mixed>  $paymentPayload
     */
    public function __construct(
        public int $paymentId,
        public string $transactionId,
        public ?string $status,
        public array $paymentPayload,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'payment_id' => $this->paymentId,
            'transaction_id' => $this->transactionId,
            'status' => $this->status,
            'payload' => $this->paymentPayload,
        ];
    }
}
