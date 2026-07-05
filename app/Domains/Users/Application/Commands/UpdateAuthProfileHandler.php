<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Commands;

use App\Domains\Users\Application\Dto\AuthUserDto;
use App\Domains\Users\Contracts\AuthUserRepository;
use App\Domains\Users\Support\AuthUserDtoMapper;

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
