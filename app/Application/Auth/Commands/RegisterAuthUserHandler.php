<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\Dto\AuthTokenResultDto;
use App\Application\Auth\Support\AuthUserDtoMapper;
use App\Enums\RoleName;
use App\Models\User;

final class RegisterAuthUserHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AuthUserDtoMapper $authUserDtoMapper,
    ) {}

    /**
     * Execute auth register command.
     */
    public function handle(RegisterAuthUserCommand $command): AuthTokenResultDto
    {
        $input = $command->input;

        $user = User::query()->create([
            'first_name' => $input->firstName,
            'last_name' => $input->lastName,
            'name' => trim($input->firstName.' '.$input->lastName),
            'email' => $input->email,
            'phone' => $input->phone,
            'password' => $input->password,
        ]);

        $user->assignRole(RoleName::CUSTOMER);
        $user->sendEmailVerificationNotification();

        return new AuthTokenResultDto(
            token: $user->createToken('api-register')->plainTextToken,
            user: $this->authUserDtoMapper->map($user),
        );
    }
}
