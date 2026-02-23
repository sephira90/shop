<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

final readonly class LoginAuthUserCommand
{
    /**
     * Create command payload for auth login flow.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
    ) {}
}
