<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Commands;

final readonly class CreateAdminProductCommand
{
    /**
     * Create command payload for admin product create flow.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
    ) {}
}
