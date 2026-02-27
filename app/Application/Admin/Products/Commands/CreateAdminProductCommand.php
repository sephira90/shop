<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Commands;

use App\Application\Admin\Products\Dto\CreateAdminProductInputDto;

final readonly class CreateAdminProductCommand
{
    public function __construct(
        public CreateAdminProductInputDto $input,
    ) {}
}
