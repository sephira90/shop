<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\Support\AuthUserPayloadBuilder;
use App\Enums\RoleName;
use App\Models\User;

final class RegisterAuthUserHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AuthUserPayloadBuilder $authUserPayloadBuilder,
    ) {}

    /**
     * Execute auth register command.
     *
     * @return array{token:string,user:array<string,mixed>}
     */
    public function handle(RegisterAuthUserCommand $command): array
    {
        $payload = $command->payload;

        $user = User::query()->create([
            'first_name' => $payload['first_name'],
            'last_name' => $payload['last_name'],
            'name' => trim($payload['first_name'].' '.$payload['last_name']),
            'email' => $payload['email'],
            'phone' => $payload['phone'] ?? null,
            'password' => $payload['password'],
        ]);

        $user->assignRole(RoleName::CUSTOMER);
        $user->sendEmailVerificationNotification();

        return [
            'token' => $user->createToken('api-register')->plainTextToken,
            'user' => $this->authUserPayloadBuilder->build($user),
        ];
    }
}
