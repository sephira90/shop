<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Commands;

use App\Application\Admin\Promotions\Dto\CreateAdminPromotionInputDto;

final readonly class CreateAdminPromotionCommand
{
    public function __construct(
        public CreateAdminPromotionInputDto $input,
    ) {}
}
