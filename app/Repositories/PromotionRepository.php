<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Application\Admin\Promotions\Dto\AdminPromotionListFilterDto;
use App\Models\Promotion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class PromotionRepository
{
    /**
     * List promotions for admin panel with typed filters.
     *
     * @return LengthAwarePaginator<int, Promotion>
     */
    public function paginateForAdmin(AdminPromotionListFilterDto $filter): LengthAwarePaginator
    {
        $query = Promotion::query()
            ->with(['coupons' => static fn ($couponQuery) => $couponQuery->latest('id')]);

        if ($filter->search !== null) {
            $like = '%'.$filter->search.'%';

            $query->where(static function (Builder $builder) use ($like): void {
                $builder
                    ->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhereHas('coupons', static function (Builder $couponBuilder) use ($like): void {
                        $couponBuilder->where('code', 'like', $like);
                    });
            });
        }

        if ($filter->isActive !== null) {
            $query->where('is_active', $filter->isActive);
        }

        return $query
            ->latest('id')
            ->paginate($filter->perPage, ['*'], 'page', $filter->page);
    }
}
