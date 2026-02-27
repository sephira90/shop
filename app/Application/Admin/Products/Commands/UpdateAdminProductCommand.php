<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Commands;

use App\Application\Admin\Products\Dto\UpdateAdminProductInputDto;
use App\Models\Product;

final readonly class UpdateAdminProductCommand
{
    public function __construct(
        public Product $product,
        public UpdateAdminProductInputDto $input,
    ) {}
}
