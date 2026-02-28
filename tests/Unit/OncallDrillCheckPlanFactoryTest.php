<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Observability\ObservabilityReportCommandInvocationFactory;
use App\Support\Oncall\OncallDrillCheckPlanFactory;
use Tests\TestCase;

class OncallDrillCheckPlanFactoryTest extends TestCase
{
    public function test_build_returns_core_dry_run_checks_by_default(): void
    {
        config()->set('observability.alerts.minutes', 90);
        config()->set('observability.alerts.source', 'smoke');
        config()->set('observability.alerts.max_api_slow_rate', 0.25);
        config()->set('observability.alerts.max_webhook_lag_warn_rate', 0.15);
        config()->set('observability.alerts.require_api_samples', false);
        config()->set('observability.alerts.require_webhook_samples', true);

        $checks = (new OncallDrillCheckPlanFactory(
            new ObservabilityReportCommandInvocationFactory,
        ))->build(false, false);

        $this->assertCount(3, $checks);
        $this->assertSame('oncall_healthcheck', $checks[0]->name);
        $this->assertSame('app:healthcheck', $checks[0]->command);
        $this->assertSame('oncall_observability_slo_report', $checks[1]->name);
        $this->assertSame([
            '--minutes' => 90,
            '--source' => 'smoke',
            '--max-api-slow-rate' => 0.25,
            '--max-webhook-lag-warn-rate' => 0.15,
            '--require-api-samples' => false,
            '--require-webhook-samples' => true,
        ], $checks[1]->parameters);
        $this->assertSame(['--dry-run' => true], $checks[2]->parameters);
    }

    public function test_build_adds_write_smokes_and_persist_flag_when_requested(): void
    {
        $checks = (new OncallDrillCheckPlanFactory(
            new ObservabilityReportCommandInvocationFactory,
        ))->build(true, true);

        $this->assertCount(5, $checks);
        $this->assertSame('oncall_api_contract_smoke', $checks[3]->name);
        $this->assertSame('app:api-contract-smoke', $checks[3]->command);
        $this->assertSame(['--persist' => true], $checks[3]->parameters);
        $this->assertSame('oncall_webhook_flow_smoke', $checks[4]->name);
        $this->assertSame('app:webhook-flow-smoke', $checks[4]->command);
        $this->assertSame(['--persist' => true], $checks[4]->parameters);
    }
}
