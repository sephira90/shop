<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Observability\ObservabilityReportOptionsResolver;
use App\Support\Observability\ObservabilityReportOutputBuilder;
use App\Support\Observability\ObservabilityReportRunner;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;

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
    public function __construct(
        private readonly ObservabilityReportOptionsResolver $optionsResolver,
        private readonly ObservabilityReportRunner $reportRunner,
        private readonly ObservabilityReportOutputBuilder $outputBuilder,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $options = $this->optionsResolver->resolve([
                'minutes' => $this->option('minutes'),
                'source' => $this->option('source'),
                'max_api_slow_rate' => $this->option('max-api-slow-rate'),
                'max_webhook_lag_warn_rate' => $this->option('max-webhook-lag-warn-rate'),
                'require_api_samples' => (bool) $this->option('require-api-samples'),
                'require_webhook_samples' => (bool) $this->option('require-webhook-samples'),
                'json' => (bool) $this->option('json'),
            ]);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $result = $this->reportRunner->run($options);

        try {
            $output = $this->outputBuilder->build($result);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($result->warnings as $warning) {
            $this->warn($warning);
        }

        if ($output->jsonOutput !== null) {
            $this->line($output->jsonOutput);
        } else {
            $this->table($output->summaryHeaders, $output->summaryRows);

            if ($output->catalogRows === []) {
                if ($output->catalogEmptyMessage !== null) {
                    $this->line($output->catalogEmptyMessage);
                }
            } else {
                $this->table($output->catalogHeaders, $output->catalogRows);
            }

            if ($output->webhookRows === []) {
                if ($output->webhookEmptyMessage !== null) {
                    $this->line($output->webhookEmptyMessage);
                }
            } else {
                $this->table($output->webhookHeaders, $output->webhookRows);
            }
        }

        if ($result->passed()) {
            $this->info('Observability report generated.');

            return self::SUCCESS;
        }

        foreach ($result->violations as $violation) {
            $this->error($violation);
        }

        $this->error('Observability threshold checks failed.');

        return self::FAILURE;
    }
}
