<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Order\StatusTransitionSource;
use App\Enums\PaymentStatus;
use App\Events\PaymentStatusChanged;
use App\Jobs\DispatchShipmentJob;
use App\Jobs\SendOrderConfirmationJob;
use App\Listeners\QueuePaymentStatusSideEffects;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class QueuePaymentStatusSideEffectsTest extends TestCase
{
    public function test_handle_dispatches_notification_and_shipment_jobs_on_first_capture_transition(): void
    {
        Bus::fake();

        app(QueuePaymentStatusSideEffects::class)->handle(new PaymentStatusChanged(
            orderId: 'order-captured',
            paymentId: 'payment-captured',
            previousStatus: PaymentStatus::PENDING,
            currentStatus: PaymentStatus::CAPTURED,
            source: StatusTransitionSource::PAYMENT_WEBHOOK,
        ));

        Bus::assertDispatched(
            SendOrderConfirmationJob::class,
            fn (SendOrderConfirmationJob $job): bool => $job->orderId === 'order-captured',
        );
        Bus::assertDispatched(
            DispatchShipmentJob::class,
            fn (DispatchShipmentJob $job): bool => $job->orderId === 'order-captured',
        );
    }

    public function test_handle_skips_side_effect_jobs_for_non_capture_or_duplicate_capture_transition(): void
    {
        Bus::fake();

        $listener = app(QueuePaymentStatusSideEffects::class);

        $listener->handle(new PaymentStatusChanged(
            orderId: 'order-authorized',
            paymentId: 'payment-authorized',
            previousStatus: PaymentStatus::PENDING,
            currentStatus: PaymentStatus::AUTHORIZED,
            source: StatusTransitionSource::PAYMENT_WEBHOOK,
        ));

        $listener->handle(new PaymentStatusChanged(
            orderId: 'order-duplicate-capture',
            paymentId: 'payment-duplicate-capture',
            previousStatus: PaymentStatus::CAPTURED,
            currentStatus: PaymentStatus::CAPTURED,
            source: StatusTransitionSource::PAYMENT_WEBHOOK,
        ));

        Bus::assertNothingDispatched();
    }
}
