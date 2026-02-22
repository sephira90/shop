<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Observability\ObservabilityAlertRouter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class AppObservabilityAlertCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:observability-alert-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run observability SLO checks and route failure alerts.';

    /**
     * Create command instance.
     */
    public function __construct(private readonly ObservabilityAlertRouter $alertRouter)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $parameters = $this->buildObservabilityReportParameters();
        $exitCode = Artisan::call('app:observability-report', $parameters);
        $reportOutput = trim(Artisan::output());

        if ($reportOutput !== '') {
            $this->line($reportOutput);
        }

        if ($exitCode === self::SUCCESS) {
            $this->info('Observability alert check passed.');

            return self::SUCCESS;
        }

        $routingResult = $this->alertRouter->routeFailureAlert([
            'command' => 'app:observability-report',
            'exit_code' => $exitCode,
            'output' => $reportOutput,
            'parameters' => $this->stringifyParameters($parameters),
            'happened_at' => now()->toIso8601String(),
        ]);

        if ($routingResult['suppressed']) {
            $this->warn('Observability alert routing suppressed by cooldown window.');
        } elseif ($routingResult['sent'] === []) {
            $this->warn('Observability alert routing skipped: no channels configured or delivery failed.');
        } else {
            $this->error(sprintf(
                'Observability alerts sent via: %s.',
                implode(', ', $routingResult['sent']),
            ));
        }

        return self::FAILURE;
    }

    /**
     * Build options for app:observability-report call.
     *
     * @return array<string,bool|float|int>
     */
    private function buildObservabilityReportParameters(): array
    {
        return [
            '--minutes' => (int) config('observability.alerts.minutes', 120),
            '--max-api-slow-rate' => (float) config('observability.alerts.max_api_slow_rate', 0.30),
            '--max-webhook-lag-warn-rate' => (float) config('observability.alerts.max_webhook_lag_warn_rate', 0.30),
            '--require-api-samples' => (bool) config('observability.alerts.require_api_samples', true),
            '--require-webhook-samples' => (bool) config('observability.alerts.require_webhook_samples', true),
        ];
    }

    /**
     * Cast command options to string map for notification payload.
     *
     * @param  array<string,bool|float|int>  $parameters
     * @return array<string,string>
     */
    private function stringifyParameters(array $parameters): array
    {
        $result = [];

        foreach ($parameters as $key => $value) {
            if (is_bool($value)) {
                $result[$key] = $value ? 'true' : 'false';

                continue;
            }

            $result[$key] = (string) $value;
        }

        return $result;
    }
}
