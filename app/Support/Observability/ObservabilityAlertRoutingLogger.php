<?php

declare(strict_types=1);

namespace App\Support\Observability;

use Illuminate\Support\Facades\Log;

final class ObservabilityAlertRoutingLogger
{
    /**
     * @param  array<string,mixed>  $context
     */
    public function warning(string $channel, string $message, array $context = []): void
    {
        Log::warning('observability.alert_routing_warning', [
            'channel' => $channel,
            'message' => $message,
            ...$context,
        ]);
    }

    /**
     * Emit the aggregate operational signal when at least one enabled
     * delivery was attempted and every attempt failed.
     *
     * @param  list<string>  $channels
     */
    public function aggregateFailure(array $channels): void
    {
        Log::warning('observability.alert_routing_aggregate_failure', [
            'channels' => $channels,
        ]);
    }
}
