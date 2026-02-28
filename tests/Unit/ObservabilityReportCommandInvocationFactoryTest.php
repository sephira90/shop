<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Observability\ObservabilityReportCommandInvocationFactory;
use Tests\TestCase;

class ObservabilityReportCommandInvocationFactoryTest extends TestCase
{
    public function test_make_from_alert_config_uses_alert_threshold_settings(): void
    {
        config()->set('observability.alerts.minutes', 90);
        config()->set('observability.alerts.source', 'smoke');
        config()->set('observability.alerts.max_api_slow_rate', 0.25);
        config()->set('observability.alerts.max_webhook_lag_warn_rate', 0.15);
        config()->set('observability.alerts.require_api_samples', false);
        config()->set('observability.alerts.require_webhook_samples', true);

        $invocation = (new ObservabilityReportCommandInvocationFactory)->makeFromAlertConfig();

        $this->assertSame('app:observability-report', $invocation->command);
        $this->assertSame([
            '--minutes' => 90,
            '--source' => 'smoke',
            '--max-api-slow-rate' => 0.25,
            '--max-webhook-lag-warn-rate' => 0.15,
            '--require-api-samples' => false,
            '--require-webhook-samples' => true,
        ], $invocation->parameters);
        $this->assertSame([
            '--minutes' => '90',
            '--source' => 'smoke',
            '--max-api-slow-rate' => '0.25',
            '--max-webhook-lag-warn-rate' => '0.15',
            '--require-api-samples' => 'false',
            '--require-webhook-samples' => 'true',
        ], $invocation->stringifyParameters());
    }
}
