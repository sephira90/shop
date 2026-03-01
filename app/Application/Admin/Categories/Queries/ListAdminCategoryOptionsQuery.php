<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Queries;

use App\Application\Admin\Categories\Dto\AdminCategoryOptionListFilterDto;

final readonly class ListAdminCategoryOptionsQuery
{
    /**
     * Create query payload for admin category selector options.
     */
    public function __construct(
        public AdminCategoryOptionListFilterDto $filter,
    ) {}
}
