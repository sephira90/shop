<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class AppOncallDrillSmokeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:oncall-drill-smoke
        {--with-write-smokes : Include write-path smoke commands (api-contract/webhook-flow)}
        {--persist : Pass --persist to write-path smoke commands}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run on-call drill checks and print escalation guidance.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $results = [];
        $failures = [];

        foreach ($this->buildChecks() as $check) {
            $exitCode = Artisan::call($check['command'], $check['parameters']);
            $output = trim(Artisan::output());
            $status = $exitCode === self::SUCCESS ? 'ok' : 'fail';

            $results[] = [
                'check' => $check['name'],
                'command' => $check['command'],
                'status' => $status,
                'exit_code' => $exitCode,
            ];

            if ($exitCode !== self::SUCCESS) {
                $escalation = $this->escalationForCheck($check['name']);
                $failures[] = [
                    'check' => $check['name'],
                    'severity' => $escalation['severity'],
                    'owner' => $escalation['owner'],
                    'next_step' => $escalation['next_step'],
                    'output_excerpt' => Str::limit($output, 320),
                ];
            }
        }

        $this->table(
            ['check', 'command', 'status', 'exit_code'],
            array_map(static fn (array $result): array => [
                (string) $result['check'],
                (string) $result['command'],
                (string) $result['status'],
                (string) $result['exit_code'],
            ], $results),
        );

        if ($failures === []) {
            $this->info('On-call drill passed.');

            return self::SUCCESS;
        }

        $this->table(
            ['check', 'severity', 'owner', 'next_step', 'output_excerpt'],
            array_map(static fn (array $failure): array => [
                (string) $failure['check'],
                (string) $failure['severity'],
                (string) $failure['owner'],
                (string) $failure['next_step'],
                (string) $failure['output_excerpt'],
            ], $failures),
        );

        $this->error('On-call drill failed. Follow escalation matrix in docs/OPERATIONS_RUNBOOK_CHECKOUT_WEBHOOKS.md.');

        return self::FAILURE;
    }

    /**
     * Build command checks for on-call drill.
     *
     * @return list<array{
     *     name:string,
     *     command:string,
     *     parameters:array<string,bool|float|int|string>
     * }>
     */
    private function buildChecks(): array
    {
        $checks = [
            [
                'name' => 'oncall_healthcheck',
                'command' => 'app:healthcheck',
                'parameters' => [],
            ],
            [
                'name' => 'oncall_observability_slo_report',
                'command' => 'app:observability-report',
                'parameters' => [
                    '--minutes' => (int) config('observability.alerts.minutes', 120),
                    '--max-api-slow-rate' => (float) config('observability.alerts.max_api_slow_rate', 0.30),
                    '--max-webhook-lag-warn-rate' => (float) config('observability.alerts.max_webhook_lag_warn_rate', 0.30),
                    '--require-api-samples' => (bool) config('observability.alerts.require_api_samples', true),
                    '--require-webhook-samples' => (bool) config('observability.alerts.require_webhook_samples', true),
                ],
            ],
            [
                'name' => 'oncall_cleanup_dry_run',
                'command' => 'app:maintenance-cleanup',
                'parameters' => [
                    '--dry-run' => true,
                ],
            ],
        ];

        if (! (bool) $this->option('with-write-smokes')) {
            return $checks;
        }

        $persist = (bool) $this->option('persist');

        $checks[] = [
            'name' => 'oncall_api_contract_smoke',
            'command' => 'app:api-contract-smoke',
            'parameters' => $persist ? ['--persist' => true] : [],
        ];

        $checks[] = [
            'name' => 'oncall_webhook_flow_smoke',
            'command' => 'app:webhook-flow-smoke',
            'parameters' => $persist ? ['--persist' => true] : [],
        ];

        return $checks;
    }

    /**
     * Resolve escalation routing metadata for failed check.
     *
     * @return array{severity:string,owner:string,next_step:string}
     */
    private function escalationForCheck(string $check): array
    {
        return match ($check) {
            'oncall_healthcheck' => [
                'severity' => 'SEV-1',
                'owner' => 'platform-oncall',
                'next_step' => 'Stabilize db/cache connectivity and re-run healthcheck.',
            ],
            'oncall_observability_slo_report' => [
                'severity' => 'SEV-2',
                'owner' => 'api-oncall',
                'next_step' => 'Run app:observability-alert-check and investigate API/webhook SLO regression.',
            ],
            'oncall_cleanup_dry_run' => [
                'severity' => 'SEV-3',
                'owner' => 'backend-oncall',
                'next_step' => 'Validate lifecycle tables and cleanup retention config.',
            ],
            'oncall_api_contract_smoke' => [
                'severity' => 'SEV-2',
                'owner' => 'api-oncall',
                'next_step' => 'Investigate API contract regression before enabling checkout traffic changes.',
            ],
            'oncall_webhook_flow_smoke' => [
                'severity' => 'SEV-2',
                'owner' => 'fulfillment-oncall',
                'next_step' => 'Investigate payment/shipping webhook chain and idempotency flow.',
            ],
            default => [
                'severity' => 'SEV-3',
                'owner' => 'oncall',
                'next_step' => 'Review command output and run targeted diagnostics.',
            ],
        };
    }
}
