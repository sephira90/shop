<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\Contracts\AuthUserRepository;
use App\Application\Auth\Dto\AuthUserDto;
use App\Application\Auth\Support\AuthUserDtoMapper;

final class UpdateAuthProfileHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AuthUserRepository $authUserRepository,
        private readonly AuthUserDtoMapper $authUserDtoMapper,
    ) {}

    /**
     * Execute auth profile update command.
     */
    public function handle(UpdateAuthProfileCommand $command): AuthUserDto
    {
        $fresh = $this->authUserRepository->updateProfile($command->user, $command->input);

        return $this->authUserDtoMapper->map($fresh);
    }
}
