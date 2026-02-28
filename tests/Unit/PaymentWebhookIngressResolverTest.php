<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Payment\PaymentWebhookIngressResolver;
use App\Services\Webhook\WebhookIngressException;
use App\Support\Data\JsonPayload;
use Tests\TestCase;

class PaymentWebhookIngressResolverTest extends TestCase
{
    public function test_resolve_extracts_provider_identifiers_into_typed_payload(): void
    {
        $resolvedPayload = app(PaymentWebhookIngressResolver::class)->resolve(JsonPayload::fromArray([
            'event_id' => ' evt-payment-1 ',
            'transaction_id' => ' tx-payment-1 ',
            'status' => ' paid ',
        ]));

        $this->assertSame('evt-payment-1', $resolvedPayload->eventId);
        $this->assertSame('tx-payment-1', $resolvedPayload->transactionId);
        $this->assertSame('paid', $resolvedPayload->status);
    }

    public function test_prevalidate_resolved_returns_metadata_for_valid_signature(): void
    {
        $resolver = app(PaymentWebhookIngressResolver::class);
        $resolvedPayload = $resolver->resolve(JsonPayload::fromArray([
            'event_id' => 'evt-payment-valid',
            'transaction_id' => 'tx-payment-valid',
            'status' => 'paid',
        ]));

        $metadata = $resolver->prevalidateResolved(
            $resolvedPayload,
            hash('sha256', 'evt-payment-valid'),
        );

        $this->assertSame('evt-payment-valid', $metadata->eventId);
    }

    public function test_prevalidate_resolved_rejects_invalid_signature(): void
    {
        $resolver = app(PaymentWebhookIngressResolver::class);
        $resolvedPayload = $resolver->resolve(JsonPayload::fromArray([
            'event_id' => 'evt-payment-invalid-signature',
            'transaction_id' => 'tx-payment-invalid-signature',
            'status' => 'paid',
        ]));

        $this->expectException(WebhookIngressException::class);
        $this->expectExceptionMessage('Invalid webhook signature.');

        $resolver->prevalidateResolved($resolvedPayload, 'invalid-signature');
    }

    public function test_prevalidate_resolved_rejects_missing_transaction_id(): void
    {
        $resolver = app(PaymentWebhookIngressResolver::class);
        $resolvedPayload = $resolver->resolve(JsonPayload::fromArray([
            'event_id' => 'evt-payment-no-transaction',
            'status' => 'paid',
        ]));

        $this->expectException(WebhookIngressException::class);
        $this->expectExceptionMessage('Payment transaction id is required.');

        $resolver->prevalidateResolved(
            $resolvedPayload,
            hash('sha256', 'evt-payment-no-transaction'),
        );
    }
}
