<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ProductStatus;
use App\Filters\Admin\AdminOrderListFilter;
use App\Filters\Admin\AdminProductListFilter;
use App\Models\ProductVariant;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\Cart\CartService;
use App\Services\Catalog\CatalogService;
use App\Services\Checkout\CheckoutService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use DomainException;
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
        {--max-cart-ms=600 : Maximum latency for cart show path}
        {--max-cart-queries=8 : Maximum query count for cart show path}
        {--max-checkout-ms=1800 : Maximum latency for checkout place-order path}
        {--max-checkout-queries=40 : Maximum query count for checkout place-order path}
        {--max-orders-ms=800 : Maximum latency for admin orders summary}
        {--max-orders-queries=6 : Maximum query count for admin orders summary}
        {--max-admin-products-ms=900 : Maximum latency for admin products list}
        {--max-admin-products-queries=12 : Maximum query count for admin products list}';

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
        private readonly CartService $cartService,
        private readonly CheckoutService $checkoutService,
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
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
            $variantId = $this->ensureAvailableVariantId();
            $cartGuestToken = 'perf-cart-'.Str::lower(Str::random(12));
            $checkoutGuestToken = 'perf-checkout-'.Str::lower(Str::random(12));
            $checkoutPayload = $this->buildCheckoutPayload();

            $this->prepareGuestCart($cartGuestToken, $variantId);
            $this->prepareGuestCart($checkoutGuestToken, $variantId);

            $orderFilter = AdminOrderListFilter::fromValidated([
                'page' => 1,
                'per_page' => 20,
            ]);
            $productFilter = AdminProductListFilter::fromValidated([
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
                $this->measure('cart_show', function () use ($cartGuestToken): void {
                    $cart = $this->cartService->resolve(null, $cartGuestToken);
                    $this->cartService->payload($cart);
                }),
                $this->measure('checkout_place_order', function () use ($checkoutGuestToken, $checkoutPayload): void {
                    $cart = $this->cartService->resolveForCheckout(null, $checkoutGuestToken);
                    $idempotencyKey = 'perf-checkout-'.Str::lower(Str::random(16));
                    $this->checkoutService->placeOrder($cart, $checkoutPayload, $idempotencyKey);
                }, rollback: true),
                $this->measure('admin_orders_summary', function () use ($orderFilter): void {
                    $this->orderRepository->paginateSummaryForAdmin($orderFilter);
                }),
                $this->measure('admin_products_list', function () use ($productFilter): void {
                    $this->productRepository->paginateForAdmin($productFilter);
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
                $budget = $this->resolveBudget($check['name']);
                $this->assertThreshold($check, $budget['max_ms'], $budget['max_queries'], $violations);
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
    private function measure(string $name, callable $callback, bool $rollback = false): array
    {
        $connection = DB::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();

        if ($rollback) {
            DB::beginTransaction();
        }

        $startedAt = hrtime(true);
        try {
            $callback();
        } catch (\Throwable $exception) {
            if ($rollback && DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            $connection->disableQueryLog();

            throw $exception;
        }

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        $queries = count($connection->getQueryLog());

        if ($rollback && DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        $connection->disableQueryLog();

        return [
            'name' => $name,
            'duration_ms' => $durationMs,
            'queries' => $queries,
        ];
    }

    /**
     * Resolve latency/query budget per check.
     *
     * @return array{max_ms: float, max_queries: int}
     */
    private function resolveBudget(string $checkName): array
    {
        return match ($checkName) {
            'catalog_list_cold' => [
                'max_ms' => (float) $this->option('max-catalog-ms'),
                'max_queries' => (int) $this->option('max-catalog-queries'),
            ],
            'catalog_list_warm' => [
                'max_ms' => (float) $this->option('max-catalog-warm-ms'),
                'max_queries' => (int) $this->option('max-catalog-warm-queries'),
            ],
            'cart_show' => [
                'max_ms' => (float) $this->option('max-cart-ms'),
                'max_queries' => (int) $this->option('max-cart-queries'),
            ],
            'checkout_place_order' => [
                'max_ms' => (float) $this->option('max-checkout-ms'),
                'max_queries' => (int) $this->option('max-checkout-queries'),
            ],
            'admin_orders_summary' => [
                'max_ms' => (float) $this->option('max-orders-ms'),
                'max_queries' => (int) $this->option('max-orders-queries'),
            ],
            'admin_products_list' => [
                'max_ms' => (float) $this->option('max-admin-products-ms'),
                'max_queries' => (int) $this->option('max-admin-products-queries'),
            ],
            default => throw new DomainException(sprintf('Unknown performance smoke check "%s".', $checkName)),
        };
    }

    /**
     * Ensure at least one active/published variant with available stock exists.
     */
    private function ensureAvailableVariantId(): int
    {
        $variantId = $this->findAvailableVariantId();
        if ($variantId !== null) {
            return $variantId;
        }

        app(RoleSeeder::class)->run();
        app(CatalogSeeder::class)->run();

        $variantId = $this->findAvailableVariantId();
        if ($variantId === null) {
            throw new DomainException('Performance smoke precondition failed: no available variant found.');
        }

        return $variantId;
    }

    /**
     * Find one available variant for cart/checkout smoke checks.
     */
    private function findAvailableVariantId(): ?int
    {
        $variantId = ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', static function ($productQuery): void {
                $productQuery
                    ->where('status', ProductStatus::ACTIVE->value)
                    ->whereNotNull('published_at');
            })
            ->whereHas('inventory', static function ($inventoryQuery): void {
                $inventoryQuery->whereColumn('quantity', '>', 'reserved_quantity');
            })
            ->orderBy('id')
            ->value('id');

        return is_numeric($variantId) ? (int) $variantId : null;
    }

    /**
     * Prepare guest cart with one available item for performance checks.
     */
    private function prepareGuestCart(string $guestToken, int $variantId): void
    {
        $cart = $this->cartService->resolve(null, $guestToken);
        $this->cartService->upsertItem($cart, $variantId, 1);
    }

    /**
     * Build deterministic checkout payload for budget checks.
     *
     * @return array<string, mixed>
     */
    private function buildCheckoutPayload(): array
    {
        return [
            'email' => 'performance-smoke-'.Str::lower(Str::random(8)).'@shop.local',
            'billing_address' => [
                'line1' => '1 Performance Street',
                'city' => 'New York',
                'country' => 'US',
                'postcode' => '10001',
            ],
            'shipping_address' => [
                'line1' => '1 Performance Street',
                'city' => 'New York',
                'country' => 'US',
                'postcode' => '10001',
            ],
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
