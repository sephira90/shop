<?php

declare(strict_types=1);

namespace App\Repositories\Concerns;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

trait AppliesOrderSearch
{
    /**
     * @param  Builder<Order>  $query
     */
    private function applyOrderSearch(Builder $query, ?string $search): void
    {
        if ($search === null) {
            return;
        }

        $like = '%'.$search.'%';

        $query->where(static function (Builder $builder) use ($like): void {
            $builder
                ->where('order_number', 'like', $like)
                ->orWhere('email', 'like', $like);
        });
    }
}
