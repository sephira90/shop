<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Observability\Dto\ObservabilityAlertPayloadDto;
use App\Support\Observability\ObservabilityAlertRouter;
use App\Support\Orders\Dto\OrdersReconcileOutputDto;
use App\Support\Orders\OrdersReconcileOptionsResolver;
use App\Support\Orders\OrdersReconcileOutputBuilder;
use App\Support\Orders\OrdersReconcileRunner;
use Illuminate\Console\Command;
use InvalidArgumentException;

class AppOrdersReconcileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:orders-reconcile
        {--stuck-shipment-minutes= : Override the paid-without-shipment window in minutes (positive integer)}
        {--stale-pending-payment-minutes= : Override the stale pending payment window in minutes (positive integer)}
        {--failed-jobs-threshold= : Override the failed_jobs detection threshold (positive integer)}
        {--json : Output the reconciliation result as JSON}
        {--route-alerts : Route findings through the observability alert router}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect order lifecycle stuck-state: paid orders without shipment, stale pending payments, and a non-empty queue.failed_jobs table.';

    /**
     * Create command instance.
     */
    public function __construct(
        private readonly OrdersReconcileOptionsResolver $optionsResolver,
        private readonly OrdersReconcileRunner $runner,
        private readonly OrdersReconcileOutputBuilder $outputBuilder,
        private readonly ObservabilityAlertRouter $alertRouter,
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
                'stuck_shipment_minutes' => $this->option('stuck-shipment-minutes'),
                'stale_pending_payment_minutes' => $this->option('stale-pending-payment-minutes'),
                'failed_jobs_threshold' => $this->option('failed-jobs-threshold'),
                'json' => (bool) $this->option('json'),
            ]);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $result = $this->runner->run($options);
        $output = $this->outputBuilder->build($result);

        $this->renderOutput($output);

        if ($result->isClean()) {
            $this->info('Order lifecycle reconciliation: no findings.');

            return self::SUCCESS;
        }

        if ((bool) $this->option('route-alerts')) {
            $this->routeFindings($output);
        }

        $this->error('Order lifecycle reconciliation: stuck-state detected.');

        return self::FAILURE;
    }

    private function renderOutput(OrdersReconcileOutputDto $output): void
    {
        if ($output->jsonOutput !== null) {
            $this->line($output->jsonOutput);

            return;
        }

        if ($output->findingsRows === []) {
            $this->line((string) $output->cleanMessage);

            return;
        }

        $this->table($output->findingsHeaders, $output->findingsRows);
    }

    private function routeFindings(OrdersReconcileOutputDto $output): void
    {
        $payload = new ObservabilityAlertPayloadDto(
            command: 'app:orders-reconcile',
            exitCode: self::FAILURE,
            output: $output->jsonOutput ?? '',
            parameters: [],
            happenedAt: now()->toIso8601String(),
        );

        $routing = $this->alertRouter->routeFailureAlert($payload);

        if ($routing->suppressed) {
            $this->warn('Order reconciliation alert routing suppressed by cooldown window.');
        } elseif ($routing->hasSentChannels()) {
            $this->error(sprintf(
                'Order reconciliation alerts sent via: %s.',
                implode(', ', $routing->deliveredChannels),
            ));
        } elseif ($routing->everyAttemptedDeliveryFailed()) {
            $this->error(sprintf(
                'Order reconciliation alert delivery failed for every attempted channel: %s.',
                implode(', ', $routing->failedChannels),
            ));
        } else {
            $this->warn('Order reconciliation alert routing skipped: every channel is disabled by configuration.');
        }
    }
}
