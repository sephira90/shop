<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessPaymentWebhookJob;
use App\Jobs\ProcessShippingWebhookJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

final class WebhookCorrelationPropagationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_webhook_ingress_correlation_id_propagates_into_queued_job_payload(): void
    {
        Bus::fake();

        $payload = [
            'event_id' => 'evt-correlation-propagation-payment',
            'transaction_id' => 'txn-propagation-payment',
            'status' => 'paid',
        ];

        $this->withHeader('X-Signature', hash('sha256', $payload['event_id']))
            ->withHeader('X-Correlation-Id', 'ingress-cid-payment-001')
            ->postJson('/api/v1/webhooks/payment', $payload)
            ->assertAccepted();

        Bus::assertDispatched(
            ProcessPaymentWebhookJob::class,
            static fn (ProcessPaymentWebhookJob $job): bool => $job->eventId === 'evt-correlation-propagation-payment'
                && $job->correlationId === 'ingress-cid-payment-001',
        );
    }

    public function test_shipping_webhook_ingress_correlation_id_propagates_into_queued_job_payload(): void
    {
        Bus::fake();

        $payload = [
            'event_id' => 'evt-correlation-propagation-shipping',
            'tracking_number' => 'TRK-PROP-001',
            'status' => 'delivered',
        ];

        $this->withHeader('X-Signature', hash('sha256', $payload['event_id']))
            ->withHeader('X-Correlation-Id', 'ingress-cid-shipping-001')
            ->postJson('/api/v1/webhooks/shipping', $payload)
            ->assertOk();

        Bus::assertDispatched(
            ProcessShippingWebhookJob::class,
            static fn (ProcessShippingWebhookJob $job): bool => $job->eventId === 'evt-correlation-propagation-shipping'
                && $job->correlationId === 'ingress-cid-shipping-001',
        );
    }

    public function test_webhook_ingress_without_correlation_header_still_dispatches_job_with_generated_id(): void
    {
        Bus::fake();

        $payload = [
            'event_id' => 'evt-correlation-propagation-missing-header',
            'transaction_id' => 'txn-propagation-missing-header',
            'status' => 'paid',
        ];

        $this->withHeader('X-Signature', hash('sha256', $payload['event_id']))
            ->postJson('/api/v1/webhooks/payment', $payload)
            ->assertAccepted();

        Bus::assertDispatched(
            ProcessPaymentWebhookJob::class,
            static fn (ProcessPaymentWebhookJob $job): bool => $job->eventId === 'evt-correlation-propagation-missing-header'
                && $job->correlationId !== ''
                && $job->correlationId !== $job->eventId,
        );
    }
}
