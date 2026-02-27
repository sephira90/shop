<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\Dto\AuthUserDto;
use App\Application\Auth\Support\AuthUserDtoMapper;
use App\Models\User;

final class UpdateAuthProfileHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AuthUserDtoMapper $authUserDtoMapper,
    ) {}

    /**
     * Execute auth profile update command.
     */
    public function handle(UpdateAuthProfileCommand $command): AuthUserDto
    {
        $input = $command->input;

        $command->user->update([
            'first_name' => $input->firstName,
            'last_name' => $input->lastName,
            'name' => trim($input->firstName.' '.$input->lastName),
            'phone' => $input->phone,
        ]);

        $fresh = $command->user->fresh();

        return $this->authUserDtoMapper->map(
            $fresh instanceof User ? $fresh : $command->user
        );
    }
}
