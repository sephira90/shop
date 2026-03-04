<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Order\StatusTransitionSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Events\OrderStatusChanged;
use App\Events\PaymentStatusChanged;
use App\Events\ShipmentStatusChanged;
use App\Listeners\RecordOrderStatusTransitionMetric;
use App\Listeners\RecordPaymentStatusTransitionMetric;
use App\Listeners\RecordShipmentStatusTransitionMetric;
use App\Support\Observability\ObservabilityMetricStore;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StatusTransitionMetricListenersTest extends TestCase
{
    public function test_record_order_status_transition_metric_listener_reports_transition(): void
    {
        config()->set('observability.enabled', true);
        config()->set('observability.channel', 'null');
        Cache::flush();

        app(RecordOrderStatusTransitionMetric::class)->handle(new OrderStatusChanged(
            orderId: 'order-1',
            previousStatus: OrderStatus::PENDING,
            currentStatus: OrderStatus::PAID,
            source: StatusTransitionSource::ADMIN_ORDER_UPDATE,
        ));

        $this->assertSame([
            [
                'domain' => 'order',
                'previous_status' => 'pending',
                'current_status' => 'paid',
                'count' => 1,
            ],
        ], app(ObservabilityMetricStore::class)->statusTransitionMetrics(60, 'runtime'));
    }

    public function test_record_payment_status_transition_metric_listener_reports_transition(): void
    {
        config()->set('observability.enabled', true);
        config()->set('observability.channel', 'null');
        Cache::flush();

        app(RecordPaymentStatusTransitionMetric::class)->handle(new PaymentStatusChanged(
            orderId: 'order-1',
            paymentId: 'payment-1',
            previousStatus: PaymentStatus::PENDING,
            currentStatus: PaymentStatus::CAPTURED,
            source: StatusTransitionSource::PAYMENT_WEBHOOK,
        ));

        $this->assertSame([
            [
                'domain' => 'payment',
                'previous_status' => 'pending',
                'current_status' => 'captured',
                'count' => 1,
            ],
        ], app(ObservabilityMetricStore::class)->statusTransitionMetrics(60, 'runtime'));
    }

    public function test_record_shipment_status_transition_metric_listener_reports_transition(): void
    {
        config()->set('observability.enabled', true);
        config()->set('observability.channel', 'null');
        Cache::flush();

        app(RecordShipmentStatusTransitionMetric::class)->handle(new ShipmentStatusChanged(
            orderId: 'order-1',
            shipmentId: 'shipment-1',
            previousStatus: ShipmentStatus::PACKED,
            currentStatus: ShipmentStatus::DELIVERED,
            source: StatusTransitionSource::SHIPPING_WEBHOOK,
        ));

        $this->assertSame([
            [
                'domain' => 'shipment',
                'previous_status' => 'packed',
                'current_status' => 'delivered',
                'count' => 1,
            ],
        ], app(ObservabilityMetricStore::class)->statusTransitionMetrics(60, 'runtime'));
    }
}
