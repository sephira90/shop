<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Support\Data\TypedValue;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class OperationalSchedulerWiringGuardrailTest extends TestCase
{
    public function test_critical_operational_commands_are_scheduled_with_expected_cadence_and_overlap_policy(): void
    {
        $schedule = $this->app->make(Schedule::class);

        $maintenanceCleanup = $this->findEvent($schedule, 'app:maintenance-cleanup');
        $this->assertSame(TypedValue::string(config('cleanup.schedule.cron', '17 * * * *')), $maintenanceCleanup->expression);
        $this->assertTrue($maintenanceCleanup->withoutOverlapping);
        $this->assertFalse($maintenanceCleanup->runInBackground);

        $observabilityAlertCheck = $this->findEvent($schedule, 'app:observability-alert-check');
        $this->assertSame(TypedValue::string(config('observability.alerts.cron', '*/30 * * * *')), $observabilityAlertCheck->expression);
        $this->assertTrue($observabilityAlertCheck->withoutOverlapping);
        $this->assertFalse($observabilityAlertCheck->runInBackground);

        $ordersReconcile = $this->findEvent($schedule, 'app:orders-reconcile');
        $this->assertSame(TypedValue::string(config('orders.reconciliation.cron', '*/15 * * * *')), $ordersReconcile->expression);
        $this->assertTrue($ordersReconcile->withoutOverlapping);
        $this->assertFalse($ordersReconcile->runInBackground);

        $oncallDrill = $this->findEvent($schedule, 'app:oncall-drill-smoke');
        $this->assertSame(TypedValue::string(config('oncall.drill.cron', '45 3 * * *')), $oncallDrill->expression);
        $this->assertTrue($oncallDrill->withoutOverlapping);
        $this->assertFalse($oncallDrill->runInBackground);
        $this->assertStringNotContainsString('--with-write-smokes', (string) $oncallDrill->command);
        $this->assertStringNotContainsString('--persist', (string) $oncallDrill->command);
    }

    public function test_write_smokes_and_observability_report_are_not_directly_scheduled(): void
    {
        $schedule = $this->app->make(Schedule::class);
        $commands = array_values(array_map(
            static fn (Event $event): string => (string) ($event->command ?? ''),
            $schedule->events(),
        ));

        foreach ([
            'app:api-contract-smoke',
            'app:webhook-flow-smoke',
            'app:performance-smoke',
            'app:observability-report',
        ] as $command) {
            $this->assertFalse(
                $this->containsScheduledCommand($commands, $command),
                sprintf('Command [%s] must not be scheduled directly.', $command),
            );
        }
    }

    private function findEvent(Schedule $schedule, string $command): Event
    {
        foreach ($schedule->events() as $event) {
            if (str_contains((string) ($event->command ?? ''), $command)) {
                return $event;
            }
        }

        $this->fail(sprintf('Scheduled command [%s] was not found.', $command));
    }

    /**
     * @param  list<string>  $commands
     */
    private function containsScheduledCommand(array $commands, string $command): bool
    {
        foreach ($commands as $scheduledCommand) {
            if (str_contains($scheduledCommand, $command)) {
                return true;
            }
        }

        return false;
    }
}
