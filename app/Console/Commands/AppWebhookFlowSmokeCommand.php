<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Smoke\WebhookFlow\WebhookFlowSmokeOutputBuilder;
use App\Support\Smoke\WebhookFlow\WebhookFlowSmokeRunner;
use Illuminate\Console\Command;
use Throwable;

class AppWebhookFlowSmokeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:webhook-flow-smoke
        {--persist : Persist smoke records instead of rolling them back in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run checkout and webhook integration smoke checks with idempotency guards.';

    /**
     * Create command instance.
     */
    public function __construct(
        private readonly WebhookFlowSmokeRunner $runner,
        private readonly WebhookFlowSmokeOutputBuilder $outputBuilder,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $result = $this->runner->run((bool) $this->option('persist'));
            $output = $this->outputBuilder->build($result);
        } catch (Throwable $exception) {
            $this->error('Webhook flow smoke failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table($output->headers, $output->rows);

        if ($output->warningMessage !== null) {
            $this->warn($output->warningMessage);
        }

        $this->info($output->successMessage);

        return self::SUCCESS;
    }
}
