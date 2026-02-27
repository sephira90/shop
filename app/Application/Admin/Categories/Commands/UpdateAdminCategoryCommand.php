<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Commands;

use App\Application\Admin\Categories\Dto\UpdateAdminCategoryInputDto;
use App\Models\Category;

final readonly class UpdateAdminCategoryCommand
{
    public function __construct(
        public Category $category,
        public UpdateAdminCategoryInputDto $input,
    ) {}
}
