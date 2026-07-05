<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Application\Commands;

use App\Domains\Checkout\Application\Dto\CheckoutPlaceOrderInputDto;
use App\Models\User;

final readonly class PlaceCheckoutOrderCommand
{
    public function __construct(
        public CheckoutPlaceOrderInputDto $input,
        public string $idempotencyKey,
        public ?User $user,
    ) {}

    /**
     * Resolve optional guest token from payload.
     */
    public function guestToken(): string
    {
        return $this->input->guestToken ?? '';
    }
}
