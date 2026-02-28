<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Dto;

use App\Models\Promotion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class AdminPromotionPaginatedResultDto
{
    /**
     * @param  list<AdminPromotionResultDto>  $items
     */
    public function __construct(
        public array $items,
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
    ) {}

    /**
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     */
    public static function fromPaginator(LengthAwarePaginator $paginator): self
    {
        $items = [];
        foreach ($paginator->items() as $item) {
            if ($item instanceof Promotion) {
                $items[] = AdminPromotionResultDto::fromPromotion($item);
            }
        }

        return new self(
            items: $items,
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
            perPage: $paginator->perPage(),
            total: (int) $paginator->total(),
        );
    }

    /**
     * @return list<array{
     *     id:int,
     *     name:string,
     *     code:string|null,
     *     type:string|null,
     *     value:float,
     *     is_active:bool,
     *     usage_limit:int|null,
     *     usage_count:int,
     *     starts_at:string|null,
     *     ends_at:string|null,
     *     coupons:list<array{
     *         id:int,
     *         code:string,
     *         is_active:bool,
     *         max_redemptions:int|null,
     *         redeemed_count:int,
     *         expires_at:string|null
     *     }>,
     *     created_at:string|null,
     *     updated_at:string|null
     * }>
     */
    public function itemsToArray(): array
    {
        return array_map(
            static fn (AdminPromotionResultDto $item): array => $item->toArray(),
            $this->items
        );
    }

    /**
     * @return array{
     *     current_page:int,
     *     last_page:int,
     *     per_page:int,
     *     total:int
     * }
     */
    public function metaToArray(): array
    {
        return [
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'per_page' => $this->perPage,
            'total' => $this->total,
        ];
    }
}
