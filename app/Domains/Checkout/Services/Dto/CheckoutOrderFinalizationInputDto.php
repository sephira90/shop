<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Services\Dto;

use App\Models\Cart;
use App\Models\CheckoutIdempotency;
use App\Models\Order;

final readonly class CheckoutOrderFinalizationInputDto
{
    public function __construct(
        public Cart $lockedCart,
        public Order $order,
        public CheckoutIdempotency $idempotency,
        public CheckoutDiscountContextDto $discountContext,
        public string $requestHash,
    ) {}
}
