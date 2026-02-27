<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Commands;

use App\Application\Admin\Promotions\Dto\UpdateAdminPromotionInputDto;
use App\Models\Promotion;

final readonly class UpdateAdminPromotionCommand
{
    public function __construct(
        public Promotion $promotion,
        public UpdateAdminPromotionInputDto $input,
    ) {}
}
