<?php

declare(strict_types=1);

namespace App\Application\Cart\Commands;

use App\Models\User;

final readonly class RemoveCartItemCommand
{
    /**
     * Create command payload for cart item remove.
     */
    public function __construct(
        public ?User $user,
        public ?string $guestToken,
        public int $variantId,
    ) {}
}
