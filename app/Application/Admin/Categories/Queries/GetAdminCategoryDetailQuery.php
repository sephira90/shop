<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Queries;

use App\Models\Category;

final readonly class GetAdminCategoryDetailQuery
{
    /**
     * Create query payload for admin category detail.
     */
    public function __construct(
        public Category $category,
    ) {}
}
