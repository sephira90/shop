<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Services\Dto;

use App\Models\CheckoutIdempotency;
use App\Models\Order;

final readonly class CheckoutIdempotencyResolutionDto
{
    public function __construct(
        public CheckoutIdempotency $idempotency,
        public ?Order $existingOrder,
    ) {}
}
