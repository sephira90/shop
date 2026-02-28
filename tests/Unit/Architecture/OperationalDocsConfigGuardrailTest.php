<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class OperationalDocsConfigGuardrailTest extends TestCase
{
    /**
     * Ensure operational docs keep pointing to the active architecture roadmap.
     */
    public function test_operational_docs_reference_active_architecture_roadmap(): void
    {
        $activeRoadmap = 'docs/ARCHITECTURE_REFACTOR_NEXT.md';

        $this->assertStringContainsString(
            $activeRoadmap,
            File::get(base_path('README.md')),
        );
        $this->assertStringContainsString(
            $activeRoadmap,
            File::get(base_path('docs/OPERATIONS_RUNBOOK_CHECKOUT_WEBHOOKS.md')),
        );
    }

    /**
     * Ensure critical operational config contracts stay present and typed.
     */
    public function test_operational_config_contracts_are_present_and_typed(): void
    {
        $this->assertIsBool(config('cleanup.enabled'));
        $this->assertIsString(config('cleanup.schedule.cron'));
        $this->assertGreaterThan(0, (int) config('cleanup.retention.idempotency_hours'));
        $this->assertGreaterThan(0, (int) config('cleanup.retention.webhook_hours'));
        $this->assertGreaterThan(0, (int) config('cleanup.retention.active_cart_hours'));
        $this->assertGreaterThan(0, (int) config('cleanup.retention.inactive_cart_hours'));

        $this->assertIsBool(config('oncall.drill.enabled'));
        $this->assertIsString(config('oncall.drill.cron'));
        $this->assertIsBool(config('oncall.drill.with_write_smokes'));
        $this->assertIsBool(config('oncall.drill.persist'));

        $this->assertIsBool(config('observability.enabled'));
        $this->assertIsString(config('observability.channel'));
        $this->assertIsBool(config('observability.alerts.enabled'));
        $this->assertIsString(config('observability.alerts.cron'));
        $this->assertGreaterThan(0, (int) config('observability.alerts.minutes'));
        $this->assertContains(config('observability.alerts.source'), ['runtime', 'smoke']);
        $this->assertGreaterThanOrEqual(0.0, (float) config('observability.alerts.max_api_slow_rate'));
        $this->assertLessThanOrEqual(1.0, (float) config('observability.alerts.max_api_slow_rate'));
        $this->assertGreaterThanOrEqual(0.0, (float) config('observability.alerts.max_webhook_lag_warn_rate'));
        $this->assertLessThanOrEqual(1.0, (float) config('observability.alerts.max_webhook_lag_warn_rate'));
        $this->assertIsBool(config('observability.alerts.require_api_samples'));
        $this->assertIsBool(config('observability.alerts.require_webhook_samples'));
        $this->assertIsArray(config('observability.alerts.email.recipients'));
        $this->assertIsBool(config('observability.alerts.slack.enabled'));
        $this->assertIsBool(config('observability.alerts.pagerduty.enabled'));
    }
}
