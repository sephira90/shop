<?php

declare(strict_types=1);

namespace App\Services\Checkout\Dto;

use App\Models\CheckoutIdempotency;
use App\Models\Order;

final readonly class CheckoutIdempotencyResolutionDto
{
    public function __construct(
        public CheckoutIdempotency $idempotency,
        public ?Order $existingOrder,
    ) {}
}
