<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Str;

final class FakePaymentGateway implements PaymentGatewayInterface
{
    /**
     * {@inheritDoc}
     */
    public function createPayment(Order $order, string $idempotencyKey): array
    {
        $transactionId = 'fake_txn_'.Str::lower(Str::random(20));

        return [
            'transaction_id' => $transactionId,
            'status' => PaymentStatus::PENDING,
            'payload' => [
                'provider' => 'fake',
                'idempotency_key' => $idempotencyKey,
                'order_number' => $order->order_number,
                'checkout_url' => '/checkout/success?order='.$order->id,
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        $expected = hash('sha256', (string) ($payload['event_id'] ?? ''));

        return hash_equals($expected, $signature);
    }

    /**
     * {@inheritDoc}
     */
    public function extractEventId(array $payload): string
    {
        return (string) ($payload['event_id'] ?? '');
    }

    /**
     * {@inheritDoc}
     */
    public function extractTransactionId(array $payload): string
    {
        return (string) ($payload['transaction_id'] ?? '');
    }

    /**
     * {@inheritDoc}
     */
    public function resolveWebhookStatus(array $payload): PaymentStatus
    {
        $status = (string) ($payload['status'] ?? 'pending');

        return match ($status) {
            'authorized' => PaymentStatus::AUTHORIZED,
            'paid' => PaymentStatus::CAPTURED,
            'failed' => PaymentStatus::FAILED,
            'refunded' => PaymentStatus::REFUNDED,
            default => PaymentStatus::PENDING,
        };
    }
}
