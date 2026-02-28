<?php

declare(strict_types=1);

namespace App\Support\Maintenance;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CheckoutIdempotency;
use App\Models\WebhookReceipt;
use App\Support\Maintenance\Dto\MaintenanceCleanupResourceResultDto;
use App\Support\Maintenance\Dto\MaintenanceCleanupRetentionDto;
use App\Support\Maintenance\Dto\MaintenanceCleanupRunResultDto;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class MaintenanceCleanupExecutor
{
    public function run(
        MaintenanceCleanupRetentionDto $retention,
        bool $dryRun,
        ?CarbonImmutable $now = null,
    ): MaintenanceCleanupRunResultDto {
        $now ??= CarbonImmutable::now();

        $cutoffs = [
            'checkout_idempotencies' => $now->subHours($retention->idempotencyHours),
            'webhook_receipts' => $now->subHours($retention->webhookHours),
            'active_carts' => $now->subHours($retention->activeCartHours),
            'inactive_carts' => $now->subHours($retention->inactiveCartHours),
        ];

        return new MaintenanceCleanupRunResultDto(
            dryRun: $dryRun,
            resources: [
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
            ],
        );
    }

    /**
     * @return Builder<CheckoutIdempotency>
     */
    private function checkoutIdempotencyQuery(CarbonImmutable $cutoff): Builder
    {
        return CheckoutIdempotency::query()
            ->where('expires_at', '<=', $cutoff);
    }

    /**
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
     * @return Builder<Cart>
     */
    private function activeCartsQuery(CarbonImmutable $cutoff): Builder
    {
        return Cart::query()
            ->where('status', CartStatus::ACTIVE->value)
            ->where('updated_at', '<=', $cutoff);
    }

    /**
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
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    private function cleanupResource(
        string $resource,
        Builder $query,
        CarbonImmutable $cutoff,
        bool $dryRun,
    ): MaintenanceCleanupResourceResultDto {
        $matched = (int) (clone $query)->count();
        $affected = $dryRun ? $matched : (int) (clone $query)->delete();

        return new MaintenanceCleanupResourceResultDto(
            resource: $resource,
            cutoffUtc: $cutoff->toIso8601String(),
            matched: $matched,
            affected: $affected,
        );
    }
}
