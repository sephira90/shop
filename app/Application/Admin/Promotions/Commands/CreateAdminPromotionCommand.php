<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Commands;

final readonly class CreateAdminPromotionCommand
{
    /**
     * Create command payload for admin promotion create flow.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
    ) {}
}
