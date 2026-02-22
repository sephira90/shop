<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Queries;

use App\Models\Category;

final class GetAdminCategoryDetailHandler
{
    /**
     * Execute admin category detail query.
     */
    public function handle(GetAdminCategoryDetailQuery $query): Category
    {
        return $query->category
            ->load(['parent:id,name,slug'])
            ->loadCount(['children', 'products']);
    }
}
