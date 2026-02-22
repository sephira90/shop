<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Commands;

final readonly class CreateAdminCategoryCommand
{
    /**
     * Create command payload for admin category create flow.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
    ) {}
}
