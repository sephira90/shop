<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Domain\Order\StatusTransitionSource;
use App\Events\OrderStatusChanged;
use App\Events\PaymentStatusChanged;
use App\Events\ShipmentStatusChanged;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionNamedType;
use Tests\TestCase;

class StatusTransitionSourceBoundaryTest extends TestCase
{
    public function test_status_transition_events_use_typed_source_enum_contract(): void
    {
        $eventClasses = [
            OrderStatusChanged::class,
            PaymentStatusChanged::class,
            ShipmentStatusChanged::class,
        ];

        foreach ($eventClasses as $eventClass) {
            $reflection = new ReflectionClass($eventClass);
            $constructor = $reflection->getMethod('__construct');
            $sourceParameter = null;
            foreach ($constructor->getParameters() as $parameter) {
                if ($parameter->getName() === 'source') {
                    $sourceParameter = $parameter;
                    break;
                }
            }

            if ($sourceParameter === null) {
                $this->fail("{$eventClass} constructor must define source parameter.");
            }

            $sourceType = $sourceParameter->getType();

            $this->assertInstanceOf(
                ReflectionNamedType::class,
                $sourceType,
                "{$eventClass} source parameter must use named enum type.",
            );
            $this->assertFalse(
                $sourceType->isBuiltin(),
                "{$eventClass} source parameter must not be scalar string.",
            );
            $this->assertSame(
                StatusTransitionSource::class,
                $sourceType->getName(),
                "{$eventClass} source parameter must use StatusTransitionSource enum.",
            );
        }
    }

    public function test_status_transition_emitters_use_enum_cases_instead_of_raw_string_literals(): void
    {
        $emitterPaths = [
            app_path('Services/Payment/PaymentWebhookTransitionApplier.php'),
            app_path('Services/Shipping/ShippingWebhookTransitionApplier.php'),
            app_path('Services/Admin/AdminOrderService.php'),
        ];

        foreach ($emitterPaths as $emitterPath) {
            $source = File::get($emitterPath);

            $this->assertStringContainsString(
                'StatusTransitionSource::',
                $source,
                basename($emitterPath).' must emit typed status transition source enum.',
            );
            $this->assertStringNotContainsString(
                "source: 'payment_webhook'",
                $source,
                basename($emitterPath).' must not emit raw payment source literal.',
            );
            $this->assertStringNotContainsString(
                "source: 'shipping_webhook'",
                $source,
                basename($emitterPath).' must not emit raw shipping source literal.',
            );
            $this->assertStringNotContainsString(
                "source: 'admin_order_update'",
                $source,
                basename($emitterPath).' must not emit raw admin source literal.',
            );
        }
    }
}
