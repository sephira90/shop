<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Queries;

use App\Application\Admin\Promotions\Dto\AdminPromotionListFilterDto;

final readonly class PaginateAdminPromotionsQuery
{
    /**
     * Create query payload for admin promotions pagination.
     */
    public function __construct(
        public AdminPromotionListFilterDto $filter,
    ) {}
}
