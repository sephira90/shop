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
}
