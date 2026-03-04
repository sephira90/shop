<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Contracts;

use App\Application\Admin\Categories\Dto\AdminCategoryListFilterDto;
use App\Application\Admin\Categories\Dto\AdminCategoryOptionListFilterDto;
use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AdminCategoryReadRepository
{
    /**
     * Return minimal category selector options for admin forms.
     *
     * @return Collection<int, Category>
     */
    public function listOptionsForAdmin(AdminCategoryOptionListFilterDto $filter): Collection;

    /**
     * List categories for admin panel with typed filters.
     *
     * @return LengthAwarePaginator<int, Category>
     */
    public function paginateForAdmin(AdminCategoryListFilterDto $filter): LengthAwarePaginator;
}
