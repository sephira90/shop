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
final class ActiveCartCleanupResource extends AbstractBatchedMaintenanceCleanupResource
{
    public function resource(): string
    {
        return 'active_carts';
    }

    public function cutoff(CarbonImmutable $now, MaintenanceCleanupRetentionDto $retention): CarbonImmutable
    {
        return $now->subHours($retention->activeCartHours);
    }

    /**
     * @return Builder<Cart>
     */
    protected function query(CarbonImmutable $cutoff): Builder
    {
        return Cart::query()
            ->where('status', CartStatus::ACTIVE->value)
            ->where('updated_at', '<=', $cutoff);
    }
}
