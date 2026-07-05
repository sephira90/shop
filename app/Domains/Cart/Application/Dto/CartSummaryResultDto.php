<?php

declare(strict_types=1);

namespace App\Domains\Cart\Application\Dto;

final readonly class CartSummaryResultDto
{
    public function __construct(
        public float $subtotal,
        public float $discountTotal,
        public float $shippingTotal,
        public float $total,
    ) {}

    /**
     * @return array<string, float>
     */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discountTotal,
            'shipping_total' => $this->shippingTotal,
            'total' => $this->total,
        ];
    }
}
