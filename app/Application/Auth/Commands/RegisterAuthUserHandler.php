<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\AuthAccessTokenIssuer;
use App\Application\Auth\Contracts\AuthUserRepository;
use App\Application\Auth\Dto\AuthTokenResultDto;
use App\Application\Auth\Support\AuthUserDtoMapper;
use App\Enums\RoleName;

final class RegisterAuthUserHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AuthUserRepository $authUserRepository,
        private readonly AuthAccessTokenIssuer $authAccessTokenIssuer,
        private readonly AuthUserDtoMapper $authUserDtoMapper,
    ) {}

    /**
     * Execute auth register command.
     */
    public function handle(RegisterAuthUserCommand $command): AuthTokenResultDto
    {
        $input = $command->input;

        $user = $this->authUserRepository->createUser($input);

        $this->authUserRepository->assignRole($user, RoleName::CUSTOMER);
        $this->authUserRepository->sendEmailVerification($user);

        return new AuthTokenResultDto(
            token: $this->authAccessTokenIssuer->issue($user, 'api-register'),
            user: $this->authUserDtoMapper->map($user),
        );
    }
}
