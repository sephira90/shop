<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Events\PaymentStatusChanged;
use App\Events\ShipmentStatusChanged;
use App\Listeners\LogOrderStatusTransition;
use App\Listeners\LogShipmentStatusTransition;
use App\Listeners\QueueOrderSideEffects;
use App\Listeners\QueueOrderStatusSideEffects;
use App\Listeners\QueuePaymentStatusSideEffects;
use App\Listeners\RecordOrderStatusTransitionMetric;
use App\Listeners\RecordPaymentStatusTransitionMetric;
use App\Listeners\RecordShipmentStatusTransitionMetric;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<string, list<string>>
     */
    protected $listen = [
        OrderPlaced::class => [
            QueueOrderSideEffects::class,
        ],
        OrderStatusChanged::class => [
            LogOrderStatusTransition::class,
            QueueOrderStatusSideEffects::class,
            RecordOrderStatusTransitionMetric::class,
        ],
        PaymentStatusChanged::class => [
            QueuePaymentStatusSideEffects::class,
            RecordPaymentStatusTransitionMetric::class,
        ],
        ShipmentStatusChanged::class => [
            LogShipmentStatusTransition::class,
            RecordShipmentStatusTransitionMetric::class,
        ],
    ];
}
