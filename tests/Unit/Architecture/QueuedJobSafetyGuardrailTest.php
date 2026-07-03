<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Jobs\DispatchShipmentJob;
use App\Jobs\ProcessPaymentWebhookJob;
use App\Jobs\ProcessShippingWebhookJob;
use App\Jobs\SendOrderConfirmationJob;
use App\Jobs\SendOrderStatusChangedNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\File;
use ReflectionNamedType;
use Tests\TestCase;

class QueuedJobSafetyGuardrailTest extends TestCase
{
    public function test_post_commit_side_effect_dispatch_paths_use_after_commit_and_propagate_correlation(): void
    {
        $expectations = [
            [
                'path' => app_path('Listeners/QueueOrderSideEffects.php'),
                'markers' => [
                    'DispatchShipmentJob::dispatch(',
                    '$this->correlationContext->currentOrNew()',
                    '->afterCommit();',
                ],
            ],
            [
                'path' => app_path('Listeners/QueuePaymentStatusSideEffects.php'),
                'markers' => [
                    'SendOrderConfirmationJob::dispatch(',
                    'DispatchShipmentJob::dispatch(',
                    '$correlationId',
                    '->afterCommit();',
                ],
            ],
            [
                'path' => app_path('Listeners/QueueOrderStatusSideEffects.php'),
                'markers' => [
                    'SendOrderStatusChangedNotificationJob::dispatch(',
                    'correlationId: $this->correlationContext->currentOrNew()',
                    ')->afterCommit();',
                ],
            ],
        ];

        foreach ($expectations as $expectation) {
            $contents = File::get($expectation['path']);

            foreach ($expectation['markers'] as $marker) {
                $this->assertStringContainsString(
                    $marker,
                    $contents,
                    basename($expectation['path']).' must queue committed side effects with afterCommit() and propagate the correlation id.'
                );
            }
        }
    }

    public function test_webhook_enqueue_handlers_propagate_correlation_id_into_job_payload(): void
    {
        $expectations = [
            [
                'path' => app_path('Application/Webhook/Commands/EnqueuePaymentWebhookHandler.php'),
                'job' => 'ProcessPaymentWebhookJob::dispatch(',
            ],
            [
                'path' => app_path('Application/Webhook/Commands/EnqueueShippingWebhookHandler.php'),
                'job' => 'ProcessShippingWebhookJob::dispatch(',
            ],
        ];

        foreach ($expectations as $expectation) {
            $contents = File::get($expectation['path']);

            $this->assertStringContainsString(
                'CorrelationContext',
                $contents,
                basename($expectation['path']).' must resolve the correlation id via CorrelationContext.'
            );
            $this->assertStringContainsString(
                $expectation['job'],
                $contents,
                basename($expectation['path']).' must dispatch the webhook processing job.'
            );
            $this->assertStringContainsString(
                '$this->correlationContext->currentOrNew()',
                $contents,
                basename($expectation['path']).' must forward the current correlation id into the job payload.'
            );
        }
    }

    public function test_queued_jobs_capture_scalar_or_array_payloads_only(): void
    {
        $jobClasses = [
            DispatchShipmentJob::class,
            SendOrderConfirmationJob::class,
            SendOrderStatusChangedNotificationJob::class,
            ProcessPaymentWebhookJob::class,
            ProcessShippingWebhookJob::class,
        ];

        foreach ($jobClasses as $jobClass) {
            $reflectionClass = new \ReflectionClass($jobClass);

            $this->assertTrue(
                $reflectionClass->implementsInterface(ShouldQueue::class),
                "{$jobClass} must remain a queued job."
            );

            $constructor = $reflectionClass->getMethod('__construct');

            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();

                $this->assertInstanceOf(
                    ReflectionNamedType::class,
                    $type,
                    "{$jobClass} constructor payloads must use explicit scalar or array types."
                );

                $this->assertTrue(
                    $type->isBuiltin(),
                    "{$jobClass} must not capture Eloquent models or service objects across the queue boundary."
                );

                $this->assertContains(
                    $type->getName(),
                    ['array', 'string', 'int', 'float', 'bool'],
                    "{$jobClass} constructor payloads must stay scalar-or-array for queue safety."
                );
            }
        }
    }

    public function test_queued_jobs_carry_and_restore_correlation_id(): void
    {
        $jobClasses = [
            DispatchShipmentJob::class,
            SendOrderConfirmationJob::class,
            SendOrderStatusChangedNotificationJob::class,
            ProcessPaymentWebhookJob::class,
            ProcessShippingWebhookJob::class,
        ];

        foreach ($jobClasses as $jobClass) {
            $reflectionClass = new \ReflectionClass($jobClass);
            $constructor = $reflectionClass->getMethod('__construct');

            $hasCorrelationId = false;
            foreach ($constructor->getParameters() as $parameter) {
                if ($parameter->getName() === 'correlationId' && $parameter->getType() instanceof ReflectionNamedType && $parameter->getType()->getName() === 'string') {
                    $hasCorrelationId = true;
                }
            }

            $this->assertTrue(
                $hasCorrelationId,
                "{$jobClass} must carry a string correlationId in its queued payload."
            );

            $handleMethod = $reflectionClass->getMethod('handle');
            $handleFile = (string) $reflectionClass->getFileName();
            $handleBody = File::get($handleFile);

            $this->assertStringContainsString(
                "Log::withContext(['correlation_id' => \$this->correlationId])",
                $handleBody,
                "{$jobClass}::handle() must restore the correlation id into structured log context."
            );

            unset($handleMethod);
        }
    }

    public function test_webhook_processing_jobs_preserve_prevalidated_event_identity(): void
    {
        $expectations = [
            [
                'jobPath' => app_path('Jobs/ProcessPaymentWebhookJob.php'),
                'handlerPath' => app_path('Application/Webhook/Commands/EnqueuePaymentWebhookHandler.php'),
            ],
            [
                'jobPath' => app_path('Jobs/ProcessShippingWebhookJob.php'),
                'handlerPath' => app_path('Application/Webhook/Commands/EnqueueShippingWebhookHandler.php'),
            ],
        ];

        foreach ($expectations as $expectation) {
            $jobContents = File::get($expectation['jobPath']);
            $handlerContents = File::get($expectation['handlerPath']);

            $this->assertStringContainsString(
                'public readonly string $eventId',
                $jobContents,
                basename($expectation['jobPath']).' must carry the prevalidated webhook event identity across the queue boundary.'
            );

            $this->assertStringContainsString(
                'prevalidatedEventId: $this->eventId',
                $jobContents,
                basename($expectation['jobPath']).' must forward the prevalidated webhook event identity into processing.'
            );

            $this->assertStringContainsString(
                '$metadata->eventId',
                $handlerContents,
                basename($expectation['handlerPath']).' must enqueue the prevalidated webhook event identity.'
            );
        }
    }
}
