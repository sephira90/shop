<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

final readonly class RegisterAuthUserCommand
{
    /**
     * Create command payload for auth register flow.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
    ) {}
}
