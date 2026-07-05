<?php

declare(strict_types=1);

namespace App\Domains\Cart\Application\Commands;

use App\Domains\Cart\Application\Dto\RemoveCartItemInputDto;
use App\Models\User;

final readonly class RemoveCartItemCommand
{
    /**
     * Create command payload for cart item remove.
     */
    public function __construct(
        public ?User $user,
        public RemoveCartItemInputDto $input,
    ) {}
}
