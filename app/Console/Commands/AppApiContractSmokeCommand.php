<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Smoke\ApiContract\ApiContractSmokeOutputBuilder;
use App\Support\Smoke\ApiContract\ApiContractSmokeRunner;
use App\Support\Smoke\SmokeExecutionOptionsResolver;
use Illuminate\Console\Command;
use Throwable;

class AppApiContractSmokeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:api-contract-smoke
        {--persist : Persist smoke records instead of rolling them back in production.}
        {--only= : Comma-separated scenario names (catalog, cart, checkout, admin_products, payment_webhook, shipping_webhook).}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run API contract smoke checks for core /api/v1 endpoints.';

    /**
     * Create command instance.
     */
    public function __construct(
        private readonly SmokeExecutionOptionsResolver $optionsResolver,
        private readonly ApiContractSmokeRunner $runner,
        private readonly ApiContractSmokeOutputBuilder $outputBuilder,
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
                'persist' => (bool) $this->option('persist'),
                'only' => $this->option('only'),
            ]);
            $result = $this->runner->run($options);
            $output = $this->outputBuilder->build($result);
        } catch (Throwable $exception) {
            $this->error('API contract smoke failed: '.$exception->getMessage());

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
