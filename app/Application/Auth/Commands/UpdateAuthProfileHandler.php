<?php

declare(strict_types=1);

namespace App\Application\Auth\Commands;

use App\Application\Auth\Support\AuthUserPayloadBuilder;
use App\Models\User;

final class UpdateAuthProfileHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AuthUserPayloadBuilder $authUserPayloadBuilder,
    ) {}

    /**
     * Execute auth profile update command.
     *
     * @return array<string, mixed>
     */
    public function handle(UpdateAuthProfileCommand $command): array
    {
        $payload = $command->payload;
        $firstName = trim((string) $payload['first_name']);
        $lastName = trim((string) $payload['last_name']);
        $phone = isset($payload['phone']) ? trim((string) $payload['phone']) : '';

        $command->user->update([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => trim($firstName.' '.$lastName),
            'phone' => $phone !== '' ? $phone : null,
        ]);

        $fresh = $command->user->fresh();

        return $this->authUserPayloadBuilder->build(
            $fresh instanceof User ? $fresh : $command->user
        );
    }
}
