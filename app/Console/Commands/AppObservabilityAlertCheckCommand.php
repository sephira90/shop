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
        } elseif ($routingResult === null || ! $routingResult->hasSentChannels()) {
            $this->warn('Observability alert routing skipped: no channels configured or delivery failed.');
        } else {
            $this->error(sprintf(
                'Observability alerts sent via: %s.',
                implode(', ', $routingResult->sentChannels),
            ));
        }

        return self::FAILURE;
    }
}
