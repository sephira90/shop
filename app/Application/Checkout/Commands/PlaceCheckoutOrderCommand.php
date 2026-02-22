<?php

declare(strict_types=1);

namespace App\Application\Checkout\Commands;

use App\Models\User;

final readonly class PlaceCheckoutOrderCommand
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
        public string $idempotencyKey,
        public ?User $user,
    ) {}

    /**
     * Resolve optional guest token from payload.
     */
    public function guestToken(): string
    {
        return trim((string) ($this->payload['guest_token'] ?? ''));
    }
}
