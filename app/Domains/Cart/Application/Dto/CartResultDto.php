<?php

declare(strict_types=1);

namespace App\Domains\Cart\Application\Dto;

final readonly class CartResultDto
{
    /**
     * @param  array<int, CartItemResultDto>  $items
     */
    public function __construct(
        public string $id,
        public ?string $guestToken,
        public string $currency,
        public string $status,
        public array $items,
        public CartSummaryResultDto $summary,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'guest_token' => $this->guestToken,
            'currency' => $this->currency,
            'status' => $this->status,
            'items' => array_map(
                static fn (CartItemResultDto $item): array => $item->toArray(),
                $this->items,
            ),
            'summary' => $this->summary->toArray(),
        ];
    }
}
