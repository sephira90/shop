<?php

declare(strict_types=1);

namespace App\Support\Maintenance\Resources;

use App\Models\WebhookReceipt;
use App\Support\Maintenance\Dto\MaintenanceCleanupRetentionDto;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends AbstractBatchedMaintenanceCleanupResource<WebhookReceipt>
 */
final class WebhookReceiptCleanupResource extends AbstractBatchedMaintenanceCleanupResource
{
    public function resource(): string
    {
        return 'webhook_receipts';
    }

    public function cutoff(CarbonImmutable $now, MaintenanceCleanupRetentionDto $retention): CarbonImmutable
    {
        return $now->subHours($retention->webhookHours);
    }

    /**
     * @return Builder<WebhookReceipt>
     */
    protected function query(CarbonImmutable $cutoff): Builder
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
}
