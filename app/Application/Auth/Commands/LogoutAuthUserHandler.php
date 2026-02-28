<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\Contracts\AuthUserRepository;

final class LogoutAuthUserHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AuthUserRepository $authUserRepository,
    ) {}

    /**
     * Execute auth logout command.
     */
    public function handle(LogoutAuthUserCommand $command): void
    {
        $this->authUserRepository->revokeCurrentAccessToken($command->user);
    }
}
