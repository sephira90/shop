<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Payment\Dto\PaymentCreationResultDto;

interface PaymentGatewayInterface
{
    /**
     * Create payment in provider and return normalized payload.
     */
    public function createPayment(Order $order, string $idempotencyKey): PaymentCreationResultDto;

    /**
     * Verify webhook signature from provider.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool;

    /**
     * Resolve webhook event id.
     *
     * @param  array<string, mixed>  $payload
     */
    public function extractEventId(array $payload): string;

    /**
     * Resolve transaction id from provider payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function extractTransactionId(array $payload): string;

    /**
     * Map provider webhook payload to internal payment status.
     *
     * @param  array<string, mixed>  $payload
     */
    public function resolveWebhookStatus(array $payload): PaymentStatus;
}
