<?php

declare(strict_types=1);

namespace App\Support\Maintenance\Resources;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Support\Maintenance\Dto\MaintenanceCleanupRetentionDto;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends AbstractBatchedMaintenanceCleanupResource<Cart>
 */
final class InactiveCartCleanupResource extends AbstractBatchedMaintenanceCleanupResource
{
    public function resource(): string
    {
        return 'inactive_carts';
    }

    public function cutoff(CarbonImmutable $now, MaintenanceCleanupRetentionDto $retention): CarbonImmutable
    {
        return $now->subHours($retention->inactiveCartHours);
    }

    /**
     * @return Builder<Cart>
     */
    protected function query(CarbonImmutable $cutoff): Builder
    {
        return Cart::query()
            ->whereIn('status', [
                CartStatus::CHECKED_OUT->value,
                CartStatus::ABANDONED->value,
            ])
            ->where('updated_at', '<=', $cutoff);
    }
}
