<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Commands;

use App\Application\Admin\Categories\Dto\CreateAdminCategoryInputDto;

final readonly class CreateAdminCategoryCommand
{
    public function __construct(
        public CreateAdminCategoryInputDto $input,
    ) {}
}
