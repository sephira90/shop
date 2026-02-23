<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

final class LogoutAuthUserHandler
{
    /**
     * Execute auth logout command.
     */
    public function handle(LogoutAuthUserCommand $command): void
    {
        $command->user->currentAccessToken()->delete();
    }
}
