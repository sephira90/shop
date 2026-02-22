<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CheckoutIdempotency;
use App\Models\WebhookReceipt;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $retention = $this->resolveRetentionHours();
        if ($retention === null) {
            return self::FAILURE;
        }

        $now = CarbonImmutable::now();
        $dryRun = (bool) $this->option('dry-run');
        $cutoffs = [
            'checkout_idempotencies' => $now->subHours($retention['idempotency']),
            'webhook_receipts' => $now->subHours($retention['webhook']),
            'active_carts' => $now->subHours($retention['active_carts']),
            'inactive_carts' => $now->subHours($retention['inactive_carts']),
        ];

        $results = [
            $this->cleanupResource(
                'checkout_idempotencies',
                $this->checkoutIdempotencyQuery($cutoffs['checkout_idempotencies']),
                $cutoffs['checkout_idempotencies'],
                $dryRun,
            ),
            $this->cleanupResource(
                'webhook_receipts',
                $this->webhookReceiptsQuery($cutoffs['webhook_receipts']),
                $cutoffs['webhook_receipts'],
                $dryRun,
            ),
            $this->cleanupResource(
                'active_carts',
                $this->activeCartsQuery($cutoffs['active_carts']),
                $cutoffs['active_carts'],
                $dryRun,
            ),
            $this->cleanupResource(
                'inactive_carts',
                $this->inactiveCartsQuery($cutoffs['inactive_carts']),
                $cutoffs['inactive_carts'],
                $dryRun,
            ),
        ];

        $this->table(
            ['resource', 'cutoff_utc', 'matched', $dryRun ? 'would_delete' : 'deleted'],
            array_map(static fn (array $result): array => [
                (string) $result['resource'],
                (string) $result['cutoff_utc'],
                (string) $result['matched'],
                (string) $result[$dryRun ? 'would_delete' : 'deleted'],
            ], $results),
        );

        if ($dryRun) {
            $this->info('Dry run: no records deleted.');

            return self::SUCCESS;
        }

        $this->info('Maintenance cleanup completed.');

        return self::SUCCESS;
    }

    /**
     * Resolve retention windows from config and options.
     *
     * @return array{idempotency:int,webhook:int,active_carts:int,inactive_carts:int}|null
     */
    private function resolveRetentionHours(): ?array
    {
        $idempotency = $this->resolvePositiveIntOption(
            'idempotency-retain-hours',
            (int) config('cleanup.retention.idempotency_hours', 168),
            'idempotency-retain-hours',
        );
        $webhook = $this->resolvePositiveIntOption(
            'webhook-retain-hours',
            (int) config('cleanup.retention.webhook_hours', 720),
            'webhook-retain-hours',
        );
        $activeCarts = $this->resolvePositiveIntOption(
            'active-cart-retain-hours',
            (int) config('cleanup.retention.active_cart_hours', 720),
            'active-cart-retain-hours',
        );
        $inactiveCarts = $this->resolvePositiveIntOption(
            'inactive-cart-retain-hours',
            (int) config('cleanup.retention.inactive_cart_hours', 168),
            'inactive-cart-retain-hours',
        );

        if ($idempotency === null || $webhook === null || $activeCarts === null || $inactiveCarts === null) {
            return null;
        }

        return [
            'idempotency' => $idempotency,
            'webhook' => $webhook,
            'active_carts' => $activeCarts,
            'inactive_carts' => $inactiveCarts,
        ];
    }

    /**
     * Resolve positive integer option value.
     */
    private function resolvePositiveIntOption(string $option, int $fallback, string $label): ?int
    {
        $raw = $this->option($option);
        if ($raw === null || trim((string) $raw) === '') {
            if ($fallback > 0) {
                return $fallback;
            }

            $this->error(sprintf('Configured "%s" must be greater than 0.', $label));

            return null;
        }

        $parsed = filter_var($raw, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($parsed === false) {
            $this->error(sprintf('Option --%s must be a positive integer.', $label));

            return null;
        }

        return (int) $parsed;
    }

    /**
     * Build query for stale checkout idempotency records.
     *
     * @return Builder<CheckoutIdempotency>
     */
    private function checkoutIdempotencyQuery(CarbonImmutable $cutoff): Builder
    {
        return CheckoutIdempotency::query()
            ->where('expires_at', '<=', $cutoff);
    }

    /**
     * Build query for stale webhook receipts.
     *
     * @return Builder<WebhookReceipt>
     */
    private function webhookReceiptsQuery(CarbonImmutable $cutoff): Builder
    {
        return WebhookReceipt::query()
            ->where(static function (Builder $query) use ($cutoff): void {
                $query
                    ->where(static function (Builder $processedQuery) use ($cutoff): void {
                        $processedQuery
                            ->whereNotNull('processed_at')
                            ->where('processed_at', '<=', $cutoff);
                    })
                    ->orWhere(static function (Builder $pendingQuery) use ($cutoff): void {
                        $pendingQuery
                            ->whereNull('processed_at')
                            ->where('created_at', '<=', $cutoff);
                    });
            });
    }

    /**
     * Build query for stale active carts.
     *
     * @return Builder<Cart>
     */
    private function activeCartsQuery(CarbonImmutable $cutoff): Builder
    {
        return Cart::query()
            ->where('status', CartStatus::ACTIVE->value)
            ->where('updated_at', '<=', $cutoff);
    }

    /**
     * Build query for stale inactive carts.
     *
     * @return Builder<Cart>
     */
    private function inactiveCartsQuery(CarbonImmutable $cutoff): Builder
    {
        return Cart::query()
            ->whereIn('status', [
                CartStatus::CHECKED_OUT->value,
                CartStatus::ABANDONED->value,
            ])
            ->where('updated_at', '<=', $cutoff);
    }

    /**
     * Cleanup resource by query.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return array{resource:string,cutoff_utc:string,matched:int,would_delete?:int,deleted?:int}
     */
    private function cleanupResource(
        string $resource,
        Builder $query,
        CarbonImmutable $cutoff,
        bool $dryRun,
    ): array {
        $matched = (int) (clone $query)->count();

        if ($dryRun) {
            return [
                'resource' => $resource,
                'cutoff_utc' => $cutoff->toIso8601String(),
                'matched' => $matched,
                'would_delete' => $matched,
            ];
        }

        $deleted = (int) (clone $query)->delete();

        return [
            'resource' => $resource,
            'cutoff_utc' => $cutoff->toIso8601String(),
            'matched' => $matched,
            'deleted' => $deleted,
        ];
    }
}
