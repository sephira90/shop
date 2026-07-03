<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Jobs\SendOrderStatusChangedNotificationJob;
use App\Support\Data\TypedValue;
use App\Support\Observability\CorrelationContext;
use InvalidArgumentException;

class QueueOrderStatusSideEffects
{
    /**
     * @var array<string, true>
     */
    private array $notifiableStatuses;

    public function __construct(
        private readonly CorrelationContext $correlationContext,
    ) {
        $this->notifiableStatuses = $this->resolveNotifiableStatuses(
            TypedValue::stringList(config('orders.status_notifications.notifiable_statuses', [])),
        );
    }

    /**
     * Handle order status transition side effects.
     */
    public function handle(OrderStatusChanged $event): void
    {
        if (! $this->shouldNotify($event->currentStatus)) {
            return;
        }

        SendOrderStatusChangedNotificationJob::dispatch(
            orderId: $event->orderId,
            previousStatus: $event->previousStatus->value,
            currentStatus: $event->currentStatus->value,
            source: $event->source->value,
            correlationId: $this->correlationContext->currentOrNew(),
        )->afterCommit();
    }

    /**
     * @param  list<string>  $configuredStatuses
     * @return array<string, true>
     */
    private function resolveNotifiableStatuses(array $configuredStatuses): array
    {
        $resolved = [];

        foreach ($configuredStatuses as $configuredStatus) {
            $normalized = strtolower(trim($configuredStatus));
            $status = OrderStatus::tryFrom($normalized);

            if (! $status instanceof OrderStatus) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid orders.status_notifications.notifiable_statuses entry [%s].',
                    $configuredStatus,
                ));
            }

            $resolved[$status->value] = true;
        }

        return $resolved;
    }

    private function shouldNotify(OrderStatus $status): bool
    {
        return isset($this->notifiableStatuses[$status->value]);
    }
}
