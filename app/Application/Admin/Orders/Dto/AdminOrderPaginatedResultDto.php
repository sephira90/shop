<?php

declare(strict_types=1);

namespace App\Application\Admin\Orders\Dto;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class AdminOrderPaginatedResultDto
{
    /**
     * @param  list<AdminOrderSummaryResultDto>  $items
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
            if ($item instanceof Order) {
                $items[] = AdminOrderSummaryResultDto::fromOrder($item);
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
     *     id:string,
     *     order_number:string,
     *     email:string,
     *     status:string,
     *     payment_status:string,
     *     shipment_status:string,
     *     currency:string,
     *     total:float,
     *     placed_at:string|null,
     *     created_at:string|null
     * }>
     */
    public function itemsToArray(): array
    {
        return array_map(
            static fn (AdminOrderSummaryResultDto $item): array => $item->toArray(),
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
