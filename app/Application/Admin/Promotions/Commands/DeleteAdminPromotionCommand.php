<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Commands;

use App\Models\Promotion;

final readonly class DeleteAdminPromotionCommand
{
    /**
     * Create command payload for admin promotion delete flow.
     */
    public function __construct(
        public Promotion $promotion,
    ) {}
}
