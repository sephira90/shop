<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Domain\ValueObjects\Money;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Payment\Dto\PaymentCreationResultDto;
use App\Support\Data\JsonPayload;
use App\Support\Data\TypedValue;
use Illuminate\Support\Str;

final class FakePaymentGateway implements PaymentGatewayInterface
{
    /**
     * {@inheritDoc}
     */
    public function createPayment(Order $order, Money $amount, string $idempotencyKey): PaymentCreationResultDto
    {
        $transactionId = 'fake_txn_'.Str::lower(Str::random(20));

        return new PaymentCreationResultDto(
            transactionId: $transactionId,
            status: PaymentStatus::PENDING,
            payload: JsonPayload::fromArray([
                'provider' => 'fake',
                'idempotency_key' => $idempotencyKey,
                'order_number' => $order->order_number,
                'amount' => $amount->toFloat(),
                'currency' => $amount->currency(),
                'checkout_url' => '/checkout/success?order='.$order->id,
            ]),
        );
    }

    /**
     * {@inheritDoc}
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        $expected = hash('sha256', TypedValue::string($payload['event_id'] ?? ''));

        return hash_equals($expected, $signature);
    }

    /**
     * {@inheritDoc}
     *
     * @param  array<string, mixed>  $payload
     */
    public function extractEventId(array $payload): string
    {
        return TypedValue::string($payload['event_id'] ?? '');
    }

    /**
     * {@inheritDoc}
     *
     * @param  array<string, mixed>  $payload
     */
    public function extractTransactionId(array $payload): string
    {
        return TypedValue::string($payload['transaction_id'] ?? '');
    }

    /**
     * {@inheritDoc}
     *
     * @param  array<string, mixed>  $payload
     */
    public function resolveWebhookStatus(array $payload): PaymentStatus
    {
        $status = TypedValue::string($payload['status'] ?? 'pending');

        return match ($status) {
            'authorized' => PaymentStatus::AUTHORIZED,
            'paid' => PaymentStatus::CAPTURED,
            'failed' => PaymentStatus::FAILED,
            'refunded' => PaymentStatus::REFUNDED,
            default => PaymentStatus::PENDING,
        };
    }
}
