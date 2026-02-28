<?php

declare(strict_types=1);

namespace App\Services\Checkout\Dto;

final readonly class CheckoutRequestIdentityDto
{
    public function __construct(
        public string $scopeKey,
        public string $requestHash,
    ) {}
}
