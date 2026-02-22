<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Commands;

use App\Models\Promotion;

final readonly class UpdateAdminPromotionCommand
{
    /**
     * Create command payload for admin promotion update flow.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public Promotion $promotion,
        public array $payload,
    ) {}
}
