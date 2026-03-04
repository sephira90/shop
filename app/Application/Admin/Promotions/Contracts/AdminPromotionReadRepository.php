<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Contracts;

use App\Application\Admin\Promotions\Dto\AdminPromotionListFilterDto;
use App\Models\Promotion;
use Illuminate\Pagination\LengthAwarePaginator;

interface AdminPromotionReadRepository
{
    /**
     * List promotions for admin panel with typed filters.
     *
     * @return LengthAwarePaginator<int, Promotion>
     */
    public function paginateForAdmin(AdminPromotionListFilterDto $filter): LengthAwarePaginator;
}
