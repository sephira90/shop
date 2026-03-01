<?php

declare(strict_types=1);

namespace App\Application\Account\Orders\Dto;

use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class AccountOrderPaginatedResultDto
{
    /**
     * @param  list<AccountOrderSummaryResultDto>  $items
     */
    public function __construct(
        public array $items,
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
    ) {}

    /**
     * @param  LengthAwarePaginator<int, Order>  $paginator
     */
    public static function fromPaginator(LengthAwarePaginator $paginator): self
    {
        $items = [];
        /** @var list<Order> $paginatorItems */
        $paginatorItems = array_values($paginator->items());

        foreach ($paginatorItems as $item) {
            $items[] = AccountOrderSummaryResultDto::fromOrder($item);
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
            static fn (AccountOrderSummaryResultDto $item): array => $item->toArray(),
            $this->items
        );
    }

    /**
     * @return array{current_page:int,last_page:int,per_page:int,total:int}
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
