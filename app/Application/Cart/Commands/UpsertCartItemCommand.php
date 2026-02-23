<?php

declare(strict_types=1);

namespace App\Application\Cart\Commands;

use App\Models\User;

final readonly class UpsertCartItemCommand
{
    /**
     * Create command payload for cart item upsert.
     */
    public function __construct(
        public ?User $user,
        public ?string $guestToken,
        public int $variantId,
        public int $quantity,
    ) {}
}
