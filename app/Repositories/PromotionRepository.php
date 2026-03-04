<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Application\Admin\Promotions\Contracts\AdminPromotionReadRepository;
use App\Application\Admin\Promotions\Dto\AdminPromotionListFilterDto;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;

final class PromotionRepository implements AdminPromotionReadRepository
{
    /**
     * List promotions for admin panel with typed filters.
     *
     * @return LengthAwarePaginator<int, Promotion>
     */
    public function paginateForAdmin(AdminPromotionListFilterDto $filter): LengthAwarePaginator
    {
        $query = Promotion::query()
            ->with([
                'coupons' => static function (Relation $couponQuery): void {
                    $couponQuery->getQuery()->latest('id');
                },
            ]);

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
