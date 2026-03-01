<?php

declare(strict_types=1);

namespace App\Support\Maintenance\Resources;

use App\Models\CheckoutIdempotency;
use App\Support\Maintenance\Dto\MaintenanceCleanupRetentionDto;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends AbstractBatchedMaintenanceCleanupResource<CheckoutIdempotency>
 */
final class CheckoutIdempotencyCleanupResource extends AbstractBatchedMaintenanceCleanupResource
{
    public function resource(): string
    {
        return 'checkout_idempotencies';
    }

    public function cutoff(CarbonImmutable $now, MaintenanceCleanupRetentionDto $retention): CarbonImmutable
    {
        return $now->subHours($retention->idempotencyHours);
    }

    /**
     * @return Builder<CheckoutIdempotency>
     */
    protected function query(CarbonImmutable $cutoff): Builder
    {
        return CheckoutIdempotency::query()
            ->where('expires_at', '<=', $cutoff);
    }
}
