<?php

declare(strict_types=1);

namespace App\Support\Observability;

use App\Support\Operations\Dto\ConsoleCommandInvocationDto;

final class ObservabilityReportCommandInvocationFactory
{
    public function makeFromAlertConfig(): ConsoleCommandInvocationDto
    {
        return new ConsoleCommandInvocationDto(
            command: 'app:observability-report',
            parameters: [
                '--minutes' => (int) config('observability.alerts.minutes', 120),
                '--source' => (string) config('observability.alerts.source', config('observability.snapshot.default_source', 'runtime')),
                '--max-api-slow-rate' => (float) config('observability.alerts.max_api_slow_rate', 0.30),
                '--max-webhook-lag-warn-rate' => (float) config('observability.alerts.max_webhook_lag_warn_rate', 0.30),
                '--require-api-samples' => (bool) config('observability.alerts.require_api_samples', true),
                '--require-webhook-samples' => (bool) config('observability.alerts.require_webhook_samples', true),
            ],
        );
    }
}
