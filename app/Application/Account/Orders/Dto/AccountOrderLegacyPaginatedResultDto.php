<?php

declare(strict_types=1);

namespace App\Application\Account\Orders\Dto;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class AccountOrderLegacyPaginatedResultDto
{
    /**
     * @param  list<AccountOrderDetailResultDto>  $items
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
        foreach ($paginator->items() as $item) {
            $items[] = AccountOrderDetailResultDto::fromOrder($item);
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
     *     subtotal:float,
     *     discount_total:float,
     *     shipping_total:float,
     *     total:float,
     *     billing_address:array{line1:string|null,city:string|null,country:string|null,postcode:string|null}|null,
     *     shipping_address:array{line1:string|null,city:string|null,country:string|null,postcode:string|null}|null,
     *     items:list<array{
     *         product_variant_id:int|null,
     *         sku:string,
     *         name:string,
     *         quantity:int,
     *         unit_price:float,
     *         total_price:float
     *     }>,
     *     payments:list<array{gateway:string,transaction_id:string,status:string|null,amount:float}>,
     *     shipments:list<array{provider:string,tracking_number:string,status:string|null,cost:float}>,
     *     placed_at:string|null,
     *     created_at:string|null
     * }>
     */
    public function itemsToArray(): array
    {
        return array_map(
            static fn (AccountOrderDetailResultDto $item): array => $item->toArray(),
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
