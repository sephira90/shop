<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Observability\ObservabilityService;
use Illuminate\Console\Command;

class AppObservabilityReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:observability-report
        {--minutes=60 : Rolling snapshot window in minutes (1-1440)}
        {--source= : Metric source filter (runtime|smoke)}
        {--max-api-slow-rate= : Maximum allowed API slow-request rate in [0..1]}
        {--max-webhook-lag-warn-rate= : Maximum allowed webhook lag-warn rate in [0..1]}
        {--require-api-samples : Fail if no API samples are available in selected window}
        {--require-webhook-samples : Fail if no webhook samples are available in selected window}
        {--json : Output snapshot payload in JSON format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Print observability snapshot for API latency, catalog cache and webhook lag.';

    /**
     * Create command instance.
     */
    public function __construct(private readonly ObservabilityService $observabilityService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');

        if ($minutes < 1 || $minutes > 1440) {
            $this->error('Option --minutes must be between 1 and 1440.');

            return self::FAILURE;
        }

        $maxApiSlowRate = $this->resolveRateThresholdOption('max-api-slow-rate');
        if ($maxApiSlowRate === null && $this->option('max-api-slow-rate') !== null && $this->option('max-api-slow-rate') !== '') {
            return self::FAILURE;
        }

        $maxWebhookLagWarnRate = $this->resolveRateThresholdOption('max-webhook-lag-warn-rate');
        if ($maxWebhookLagWarnRate === null && $this->option('max-webhook-lag-warn-rate') !== null && $this->option('max-webhook-lag-warn-rate') !== '') {
            return self::FAILURE;
        }

        if (! config('observability.enabled', true)) {
            $this->warn('Observability hooks are disabled (OBSERVABILITY_ENABLED=false). Snapshot may be empty.');
        }

        $source = $this->resolveSnapshotSourceOption();
        if ($source === null) {
            return self::FAILURE;
        }

        $snapshot = $this->observabilityService->snapshot($minutes, $source);
        $violations = [
            ...$this->evaluateRequiredSamples(
                $snapshot,
                requireApiSamples: (bool) $this->option('require-api-samples'),
                requireWebhookSamples: (bool) $this->option('require-webhook-samples'),
            ),
            ...$this->evaluateThresholds($snapshot, $maxApiSlowRate, $maxWebhookLagWarnRate),
        ];

        if ((bool) $this->option('json')) {
            $encoded = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if (! is_string($encoded)) {
                $this->error('Unable to encode observability snapshot.');

                return self::FAILURE;
            }

            $this->line($encoded);

            return $this->finalizeWithThresholds($violations);
        }

        $this->table(
            ['metric', 'value'],
            [
                ['window_minutes', (string) $snapshot['minutes']],
                ['snapshot_source', (string) $snapshot['source']],
                ['api_request_count', (string) $snapshot['api']['count']],
                ['api_avg_duration_ms', $this->formatFloat((float) $snapshot['api']['avg_duration_ms'])],
                ['api_slow_count', (string) $snapshot['api']['slow_count']],
            ],
        );

        $this->renderCatalogTable($snapshot['catalog']);
        $this->renderWebhookTable($snapshot['webhook']);

        return $this->finalizeWithThresholds($violations);
    }

    /**
     * Render catalog cache metrics table.
     *
     * @param  list<array{
     *     segment:string,
     *     count:int,
     *     hit_count:int,
     *     miss_count:int,
     *     hit_ratio:float,
     *     avg_duration_ms:float,
     *     slow_miss_count:int
     * }>  $rows
     */
    private function renderCatalogTable(array $rows): void
    {
        if ($rows === []) {
            $this->line('Catalog metrics: no samples in selected window.');

            return;
        }

        $this->table(
            ['segment', 'count', 'hit', 'miss', 'hit_ratio', 'avg_ms', 'slow_miss'],
            array_map(fn (array $row): array => [
                $row['segment'],
                (string) $row['count'],
                (string) $row['hit_count'],
                (string) $row['miss_count'],
                $this->formatFloat((float) $row['hit_ratio'], 4),
                $this->formatFloat((float) $row['avg_duration_ms']),
                (string) $row['slow_miss_count'],
            ], $rows),
        );
    }

    /**
     * Render webhook metrics table.
     *
     * @param  list<array{
     *     provider:string,
     *     count:int,
     *     processed_count:int,
     *     duplicate_count:int,
     *     rejected_count:int,
     *     avg_duration_ms:float,
     *     avg_lag_ms:?float,
     *     lag_warn_count:int
     * }>  $rows
     */
    private function renderWebhookTable(array $rows): void
    {
        if ($rows === []) {
            $this->line('Webhook metrics: no samples in selected window.');

            return;
        }

        $this->table(
            ['provider', 'count', 'processed', 'duplicate', 'rejected', 'avg_ms', 'avg_lag_ms', 'lag_warn'],
            array_map(fn (array $row): array => [
                $row['provider'],
                (string) $row['count'],
                (string) $row['processed_count'],
                (string) $row['duplicate_count'],
                (string) $row['rejected_count'],
                $this->formatFloat((float) $row['avg_duration_ms']),
                $row['avg_lag_ms'] === null ? '-' : $this->formatFloat((float) $row['avg_lag_ms']),
                (string) $row['lag_warn_count'],
            ], $rows),
        );
    }

    /**
     * Format decimal for console table output.
     */
    private function formatFloat(float $value, int $precision = 2): string
    {
        return number_format($value, $precision, '.', '');
    }

    /**
     * Resolve one threshold option in [0..1].
     */
    private function resolveRateThresholdOption(string $name): ?float
    {
        $raw = $this->option($name);

        if ($raw === null || $raw === '') {
            return null;
        }

        if (! is_numeric($raw)) {
            $this->error(sprintf('Option --%s must be a number in [0..1].', $name));

            return null;
        }

        $value = (float) $raw;
        if ($value < 0 || $value > 1) {
            $this->error(sprintf('Option --%s must be between 0 and 1.', $name));

            return null;
        }

        return $value;
    }

    /**
     * Resolve metric source option.
     */
    private function resolveSnapshotSourceOption(): ?string
    {
        $rawSource = trim((string) $this->option('source'));

        if ($rawSource === '') {
            $rawSource = (string) config('observability.snapshot.default_source', 'runtime');
        }

        $source = strtolower($rawSource);

        if (! in_array($source, ['runtime', 'smoke'], true)) {
            $this->error('Option --source must be one of: runtime, smoke.');

            return null;
        }

        return $source;
    }

    /**
     * Build threshold violations list.
     *
     * @param  array{
     *     minutes:int,
     *     source:string,
     *     api:array{count:int,avg_duration_ms:float,slow_count:int},
     *     catalog:list<array{
     *         segment:string,
     *         count:int,
     *         hit_count:int,
     *         miss_count:int,
     *         hit_ratio:float,
     *         avg_duration_ms:float,
     *         slow_miss_count:int
     *     }>,
     *     webhook:list<array{
     *         provider:string,
     *         count:int,
     *         processed_count:int,
     *         duplicate_count:int,
     *         rejected_count:int,
     *         avg_duration_ms:float,
     *         avg_lag_ms:?float,
     *         lag_warn_count:int
     *     }>
     * }  $snapshot
     * @return list<string>
     */
    private function evaluateThresholds(array $snapshot, ?float $maxApiSlowRate, ?float $maxWebhookLagWarnRate): array
    {
        $violations = [];

        if ($maxApiSlowRate !== null) {
            $apiCount = (int) $snapshot['api']['count'];
            if ($apiCount > 0) {
                $slowCount = (int) $snapshot['api']['slow_count'];
                $slowRate = $slowCount / $apiCount;

                if ($slowRate > $maxApiSlowRate) {
                    $violations[] = sprintf(
                        'API slow rate exceeded: %.4f > %.4f (%d/%d).',
                        $slowRate,
                        $maxApiSlowRate,
                        $slowCount,
                        $apiCount,
                    );
                }
            } else {
                $this->warn('API threshold check skipped: no API samples in selected window.');
            }
        }

        if ($maxWebhookLagWarnRate !== null) {
            $checkedProviders = 0;

            foreach ($snapshot['webhook'] as $row) {
                $count = (int) $row['count'];
                if ($count <= 0) {
                    continue;
                }

                $checkedProviders++;
                $lagWarnCount = (int) $row['lag_warn_count'];
                $lagWarnRate = $lagWarnCount / $count;

                if ($lagWarnRate > $maxWebhookLagWarnRate) {
                    $violations[] = sprintf(
                        'Webhook lag-warn rate exceeded for provider %s: %.4f > %.4f (%d/%d).',
                        (string) $row['provider'],
                        $lagWarnRate,
                        $maxWebhookLagWarnRate,
                        $lagWarnCount,
                        $count,
                    );
                }
            }

            if ($checkedProviders === 0) {
                $this->warn('Webhook lag threshold check skipped: no webhook samples in selected window.');
            }
        }

        return $violations;
    }

    /**
     * Print threshold result and return command status.
     *
     * @param  list<string>  $violations
     */
    private function finalizeWithThresholds(array $violations): int
    {
        if ($violations === []) {
            $this->info('Observability report generated.');

            return self::SUCCESS;
        }

        foreach ($violations as $violation) {
            $this->error($violation);
        }

        $this->error('Observability threshold checks failed.');

        return self::FAILURE;
    }

    /**
     * Validate required sample guards.
     *
     * @param  array{
     *     minutes:int,
     *     source:string,
     *     api:array{count:int,avg_duration_ms:float,slow_count:int},
     *     catalog:list<array{
     *         segment:string,
     *         count:int,
     *         hit_count:int,
     *         miss_count:int,
     *         hit_ratio:float,
     *         avg_duration_ms:float,
     *         slow_miss_count:int
     *     }>,
     *     webhook:list<array{
     *         provider:string,
     *         count:int,
     *         processed_count:int,
     *         duplicate_count:int,
     *         rejected_count:int,
     *         avg_duration_ms:float,
     *         avg_lag_ms:?float,
     *         lag_warn_count:int
     *     }>
     * }  $snapshot
     * @return list<string>
     */
    private function evaluateRequiredSamples(array $snapshot, bool $requireApiSamples, bool $requireWebhookSamples): array
    {
        $violations = [];

        if ($requireApiSamples && (int) $snapshot['api']['count'] <= 0) {
            $violations[] = 'Required API samples are missing in selected window.';
        }

        if ($requireWebhookSamples) {
            $webhookSamples = 0;

            foreach ($snapshot['webhook'] as $row) {
                $webhookSamples += (int) $row['count'];
            }

            if ($webhookSamples <= 0) {
                $violations[] = 'Required webhook samples are missing in selected window.';
            }
        }

        return $violations;
    }
}
