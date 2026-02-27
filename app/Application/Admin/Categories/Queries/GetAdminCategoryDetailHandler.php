<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Queries;

use App\Application\Admin\Categories\Dto\AdminCategoryResultDto;

final class GetAdminCategoryDetailHandler
{
    /**
     * Execute admin category detail query.
     */
    public function handle(GetAdminCategoryDetailQuery $query): AdminCategoryResultDto
    {
        $category = $query->category
            ->load(['parent:id,name,slug'])
            ->loadCount(['children', 'products']);

        return AdminCategoryResultDto::fromCategory($category);
    }
}
