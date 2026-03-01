<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Jobs\DispatchShipmentJob;
use App\Jobs\ProcessPaymentWebhookJob;
use App\Jobs\ProcessShippingWebhookJob;
use App\Jobs\SendOrderConfirmationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\File;
use ReflectionNamedType;
use Tests\TestCase;

class QueuedJobSafetyGuardrailTest extends TestCase
{
    public function test_post_commit_side_effect_dispatch_paths_use_after_commit(): void
    {
        $expectations = [
            [
                'path' => app_path('Listeners/QueueOrderSideEffects.php'),
                'dispatches' => [
                    'DispatchShipmentJob::dispatch($event->order->id)->afterCommit();',
                ],
            ],
            [
                'path' => app_path('Services/Payment/PaymentWebhookTransitionApplier.php'),
                'dispatches' => [
                    'SendOrderConfirmationJob::dispatch($order->id)->afterCommit();',
                    'DispatchShipmentJob::dispatch($order->id)->afterCommit();',
                ],
            ],
        ];

        foreach ($expectations as $expectation) {
            $contents = File::get($expectation['path']);

            foreach ($expectation['dispatches'] as $dispatchExpression) {
                $this->assertStringContainsString(
                    $dispatchExpression,
                    $contents,
                    basename($expectation['path']).' must queue committed side effects with afterCommit().'
                );
            }
        }
    }

    public function test_queued_jobs_capture_scalar_or_array_payloads_only(): void
    {
        $jobClasses = [
            DispatchShipmentJob::class,
            SendOrderConfirmationJob::class,
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
