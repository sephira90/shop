<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Maintenance\MaintenanceCleanupExecutor;
use App\Support\Maintenance\MaintenanceCleanupRetentionResolver;
use Illuminate\Console\Command;
use InvalidArgumentException;

class AppMaintenanceCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:maintenance-cleanup
        {--dry-run : Show affected records without deleting them}
        {--idempotency-retain-hours= : Override checkout idempotency retention window in hours}
        {--webhook-retain-hours= : Override webhook receipt retention window in hours}
        {--active-cart-retain-hours= : Override active cart retention window in hours}
        {--inactive-cart-retain-hours= : Override inactive cart retention window in hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune stale checkout idempotency records, webhook receipts and carts.';

    public function __construct(
        private readonly MaintenanceCleanupRetentionResolver $retentionResolver,
        private readonly MaintenanceCleanupExecutor $cleanupExecutor,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $retention = $this->retentionResolver->resolve([
                'idempotency-retain-hours' => $this->option('idempotency-retain-hours'),
                'webhook-retain-hours' => $this->option('webhook-retain-hours'),
                'active-cart-retain-hours' => $this->option('active-cart-retain-hours'),
                'inactive-cart-retain-hours' => $this->option('inactive-cart-retain-hours'),
            ]);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $result = $this->cleanupExecutor->run(
            retention: $retention,
            dryRun: (bool) $this->option('dry-run'),
        );

        $this->table(
            ['resource', 'cutoff_utc', 'matched', $result->dryRun ? 'would_delete' : 'deleted'],
            array_map(static fn ($resource): array => [
                $resource->resource,
                $resource->cutoffUtc,
                (string) $resource->matched,
                (string) $resource->affected,
            ], $result->resources),
        );

        if ($result->dryRun) {
            $this->info('Dry run: no records deleted.');

            return self::SUCCESS;
        }

        $this->info('Maintenance cleanup completed.');

        return self::SUCCESS;
    }
}
