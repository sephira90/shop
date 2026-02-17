<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\PaymentStatus;
use App\Models\Order;

interface PaymentGatewayInterface
{
    /**
     * Create payment in provider and return normalized payload.
     *
     * @return array{transaction_id:string,status:PaymentStatus,payload:array<string,mixed>}
     */
    public function createPayment(Order $order, string $idempotencyKey): array;

    /**
     * Verify webhook signature from provider.
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool;

    /**
     * Resolve webhook event id.
     */
    public function extractEventId(array $payload): string;

    /**
     * Resolve transaction id from provider payload.
     */
    public function extractTransactionId(array $payload): string;

    /**
     * Map provider webhook payload to internal payment status.
     */
    public function resolveWebhookStatus(array $payload): PaymentStatus;
}
