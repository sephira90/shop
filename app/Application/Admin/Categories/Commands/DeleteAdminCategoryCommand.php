<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Commands;

use App\Models\Category;

final readonly class DeleteAdminCategoryCommand
{
    /**
     * Create command payload for admin category delete flow.
     */
    public function __construct(
        public Category $category,
    ) {}
}
