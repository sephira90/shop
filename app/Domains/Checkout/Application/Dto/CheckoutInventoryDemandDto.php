<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Application\Dto;

final readonly class CheckoutInventoryDemandDto
{
    /**
     * @param  array<int, int>  $requiredQuantityByVariant
     */
    public function __construct(
        public array $requiredQuantityByVariant,
    ) {}

    /**
     * @param  array<int, int>  $requiredQuantityByVariant
     */
    public static function fromRequiredQuantityMap(array $requiredQuantityByVariant): self
    {
        return new self($requiredQuantityByVariant);
    }
}
