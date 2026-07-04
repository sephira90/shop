<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Observability\ObservabilityAlertCheckRunner;
use Illuminate\Console\Command;

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
    public function __construct(private readonly ObservabilityAlertCheckRunner $alertCheckRunner)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $result = $this->alertCheckRunner->run();

        if ($result->reportResult->output !== '') {
            $this->line($result->reportResult->output);
        }

        if ($result->passed()) {
            $this->info('Observability alert check passed.');

            return self::SUCCESS;
        }

        $routingResult = $result->routingResult;

        if ($routingResult?->suppressed) {
            $this->warn('Observability alert routing suppressed by cooldown window.');
        } elseif ($routingResult === null) {
            $this->warn('Observability alert routing skipped: no SLO failure detected.');
        } elseif ($routingResult->hasSentChannels()) {
            $this->error(sprintf(
                'Observability alerts sent via: %s.',
                implode(', ', $routingResult->deliveredChannels),
            ));
        } elseif ($routingResult->everyAttemptedDeliveryFailed()) {
            $this->error(sprintf(
                'Observability alert delivery failed for every attempted channel: %s.',
                implode(', ', $routingResult->failedChannels),
            ));
        } else {
            $this->warn('Observability alert routing skipped: every channel is disabled by configuration.');
        }

        return self::FAILURE;
    }
}
