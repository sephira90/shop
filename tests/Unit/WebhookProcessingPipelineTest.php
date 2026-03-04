<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Webhook\Dto\WebhookIngressMetadataDto;
use App\Services\Webhook\WebhookProcessingOutcome;
use App\Services\Webhook\WebhookProcessingPipeline;
use App\Services\Webhook\WebhookProcessorAdapterInterface;
use App\Support\Data\JsonPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

final class WebhookProcessingPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_failure_context_and_rethrows_when_transition_throws(): void
    {
        config()->set('observability.enabled', false);
        $payload = JsonPayload::fromArray([
            'event_type' => 'payment.updated',
            'transaction_id' => 'txn-1',
            'status' => 'paid',
        ]);
        $expectedPayloadHash = hash('sha256', json_encode($payload->toArray(), JSON_THROW_ON_ERROR));

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context) use ($expectedPayloadHash): bool {
                return $message === 'webhook.processing_failed'
                    && $context['provider'] === 'test-provider'
                    && $context['correlation_id'] === 'evt-transition-failure'
                    && $context['event_id'] === 'evt-transition-failure'
                    && $context['event_type'] === 'payment.updated'
                    && is_int($context['receipt_id'])
                    && $context['receipt_id'] > 0
                    && $context['payload_hash'] === $expectedPayloadHash
                    && $context['outcome'] === WebhookProcessingOutcome::REJECTED->value
                    && $context['source'] === 'runtime'
                    && $context['exception_class'] === RuntimeException::class
                    && $context['exception_message'] === 'Simulated transition failure.';
            });

        $pipeline = app(WebhookProcessingPipeline::class);

        try {
            $pipeline->process(new ThrowingWebhookProcessorAdapter, $payload, 'signature');
            $this->fail('WebhookProcessingPipeline did not rethrow transition exception.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated transition failure.', $exception->getMessage());
        }
    }
}

final class ThrowingWebhookProcessorAdapter implements WebhookProcessorAdapterInterface
{
    public function receiptProvider(): string
    {
        return 'test-provider';
    }

    public function observabilityProvider(): string
    {
        return 'test-provider';
    }

    public function prevalidateIngress(JsonPayload $payload, string $signature): WebhookIngressMetadataDto
    {
        return new WebhookIngressMetadataDto(eventId: 'evt-transition-failure');
    }

    public function processTransition(JsonPayload $payload): WebhookProcessingOutcome
    {
        throw new RuntimeException('Simulated transition failure.');
    }
}
