<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Smoke\Performance\PerformanceSmokeOptionsResolver;
use App\Support\Smoke\Performance\PerformanceSmokeOutputBuilder;
use App\Support\Smoke\Performance\PerformanceSmokeRunner;
use Illuminate\Console\Command;
use Throwable;

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
        {--max-cart-ms=600 : Maximum latency for cart show path}
        {--max-cart-queries=8 : Maximum query count for cart show path}
        {--max-checkout-ms=1800 : Maximum latency for checkout place-order path}
        {--max-checkout-queries=40 : Maximum query count for checkout place-order path}
        {--max-orders-ms=800 : Maximum latency for admin orders summary}
        {--max-orders-queries=6 : Maximum query count for admin orders summary}
        {--max-admin-products-ms=900 : Maximum latency for admin products list}
        {--max-admin-products-queries=12 : Maximum query count for admin products list}
        {--persist : Persist smoke records instead of rolling them back in production.}
        {--only= : Comma-separated scenario names (catalog_list_cold, catalog_list_warm, cart_show, checkout_place_order, admin_orders_summary, admin_products_list).}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run performance smoke checks for critical query paths.';

    public function __construct(
        private readonly PerformanceSmokeOptionsResolver $optionsResolver,
        private readonly PerformanceSmokeRunner $runner,
        private readonly PerformanceSmokeOutputBuilder $outputBuilder,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $options = $this->optionsResolver->resolve([
                'persist' => (bool) $this->option('persist'),
                'only' => $this->option('only'),
                'max_catalog_ms' => $this->option('max-catalog-ms'),
                'max_catalog_queries' => $this->option('max-catalog-queries'),
                'max_catalog_warm_ms' => $this->option('max-catalog-warm-ms'),
                'max_catalog_warm_queries' => $this->option('max-catalog-warm-queries'),
                'max_cart_ms' => $this->option('max-cart-ms'),
                'max_cart_queries' => $this->option('max-cart-queries'),
                'max_checkout_ms' => $this->option('max-checkout-ms'),
                'max_checkout_queries' => $this->option('max-checkout-queries'),
                'max_orders_ms' => $this->option('max-orders-ms'),
                'max_orders_queries' => $this->option('max-orders-queries'),
                'max_admin_products_ms' => $this->option('max-admin-products-ms'),
                'max_admin_products_queries' => $this->option('max-admin-products-queries'),
            ]);
            $result = $this->runner->run($options);
            $output = $this->outputBuilder->build($result);
        } catch (Throwable $exception) {
            $this->error('Performance smoke failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table($output->headers, $output->rows);

        if ($output->warningMessage !== null) {
            $this->warn($output->warningMessage);
        }

        if (! $result->passed()) {
            foreach ($result->violations as $violation) {
                $this->error($violation);
            }

            return self::FAILURE;
        }

        $this->info($output->successMessage);

        return self::SUCCESS;
    }
}
