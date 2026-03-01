<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Application\Admin\Categories\Dto\AdminCategoryListFilterDto;
use App\Application\Admin\Categories\Dto\AdminCategoryOptionListFilterDto;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class CategoryRepository
{
    /**
     * Return minimal category selector options for admin forms.
     *
     * @return Collection<int, Category>
     */
    public function listOptionsForAdmin(AdminCategoryOptionListFilterDto $filter): Collection
    {
        $query = Category::query()
            ->select(['id', 'parent_id', 'name', 'slug', 'is_active'])
            ->orderBy('name');

        if ($filter->search !== null) {
            $like = '%'.$filter->search.'%';

            $query->where(static function (Builder $builder) use ($like): void {
                $builder
                    ->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            });
        }

        if ($filter->excludeId !== null) {
            $query->where('id', '!=', $filter->excludeId);
        }

        /** @var Collection<int, Category> $categories */
        $categories = $query->get();

        return $categories;
    }

    /**
     * List categories for admin panel with typed filters.
     *
     * @return LengthAwarePaginator<int, Category>
     */
    public function paginateForAdmin(AdminCategoryListFilterDto $filter): LengthAwarePaginator
    {
        $query = Category::query()
            ->with(['parent:id,name,slug'])
            ->withCount(['children', 'products'])
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($filter->search !== null) {
            $like = '%'.$filter->search.'%';

            $query->where(static function (Builder $builder) use ($like): void {
                $builder
                    ->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhereHas('parent', static function (Builder $parentBuilder) use ($like): void {
                        $parentBuilder->where('name', 'like', $like);
                    });
            });
        }

        if ($filter->isActive !== null) {
            $query->where('is_active', $filter->isActive);
        }

        return $query->paginate($filter->perPage, ['*'], 'page', $filter->page);
    }
}
