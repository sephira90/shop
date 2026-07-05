<?php

declare(strict_types=1);

namespace App\Domains\Cart\Application\Queries;

use App\Models\User;

final readonly class GetCurrentCartQuery
{
    /**
     * Create query payload for current cart show.
     */
    public function __construct(
        public ?User $user,
        public ?string $guestToken,
    ) {}
}
