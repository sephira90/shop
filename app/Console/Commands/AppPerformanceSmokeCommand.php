<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Filters\Admin\AdminOrderListFilter;
use App\Repositories\OrderRepository;
use App\Services\Catalog\CatalogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AppPerformanceSmokeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:performance-smoke
        {--max-catalog-ms=1200 : Maximum latency for catalog list cold path}
        {--max-catalog-queries=12 : Maximum query count for catalog list cold path}
        {--max-catalog-warm-ms=600 : Maximum latency for catalog list warm path}
        {--max-catalog-warm-queries=4 : Maximum query count for catalog list warm path}
        {--max-orders-ms=800 : Maximum latency for admin orders summary}
        {--max-orders-queries=6 : Maximum query count for admin orders summary}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run performance smoke checks for critical query paths.';

    /**
     * Create command instance.
     */
    public function __construct(
        private readonly CatalogService $catalogService,
        private readonly OrderRepository $orderRepository,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Disable observability hooks during profiling to measure business query path only.
        $observabilityEnabled = (bool) config('observability.enabled', true);
        config()->set('observability.enabled', false);

        try {
            $filters = ['_smoke_nonce' => Str::uuid()->toString()];
            $orderFilter = AdminOrderListFilter::fromValidated([
                'page' => 1,
                'per_page' => 20,
            ]);

            $checks = [
                $this->measure('catalog_list_cold', function () use ($filters): void {
                    $this->catalogService->list($filters, 12);
                }),
                $this->measure('catalog_list_warm', function () use ($filters): void {
                    $this->catalogService->list($filters, 12);
                }),
                $this->measure('admin_orders_summary', function () use ($orderFilter): void {
                    $this->orderRepository->paginateSummaryForAdmin($orderFilter);
                }),
            ];

            $this->table(
                ['check', 'duration_ms', 'queries'],
                array_map(static fn (array $check): array => [
                    $check['name'],
                    number_format((float) $check['duration_ms'], 2, '.', ''),
                    (string) $check['queries'],
                ], $checks),
            );

            $violations = [];

            foreach ($checks as $check) {
                if ($check['name'] === 'catalog_list_cold') {
                    $this->assertThreshold($check, (float) $this->option('max-catalog-ms'), (int) $this->option('max-catalog-queries'), $violations);

                    continue;
                }

                if ($check['name'] === 'catalog_list_warm') {
                    $this->assertThreshold($check, (float) $this->option('max-catalog-warm-ms'), (int) $this->option('max-catalog-warm-queries'), $violations);

                    continue;
                }

                $this->assertThreshold($check, (float) $this->option('max-orders-ms'), (int) $this->option('max-orders-queries'), $violations);
            }

            if ($violations !== []) {
                foreach ($violations as $violation) {
                    $this->error($violation);
                }

                return self::FAILURE;
            }

            $this->info('Performance smoke checks passed.');

            return self::SUCCESS;
        } finally {
            config()->set('observability.enabled', $observabilityEnabled);
        }
    }

    /**
     * Measure query count and duration for a callable.
     *
     * @return array{name: string, duration_ms: float, queries: int}
     */
    private function measure(string $name, callable $callback): array
    {
        $connection = DB::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();

        $startedAt = hrtime(true);
        $callback();
        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;

        $queries = count($connection->getQueryLog());
        $connection->disableQueryLog();

        return [
            'name' => $name,
            'duration_ms' => $durationMs,
            'queries' => $queries,
        ];
    }

    /**
     * Validate check result against thresholds.
     *
     * @param  array{name: string, duration_ms: float, queries: int}  $check
     * @param  array<int, string>  $violations
     */
    private function assertThreshold(array $check, float $maxMs, int $maxQueries, array &$violations): void
    {
        if ($check['duration_ms'] > $maxMs) {
            $violations[] = sprintf(
                '%s latency budget exceeded: %.2fms > %.2fms',
                $check['name'],
                $check['duration_ms'],
                $maxMs,
            );
        }

        if ($check['queries'] > $maxQueries) {
            $violations[] = sprintf(
                '%s query budget exceeded: %d > %d',
                $check['name'],
                $check['queries'],
                $maxQueries,
            );
        }
    }
}
