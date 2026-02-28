<?php

declare(strict_types=1);

namespace App\Application\Checkout\Dto;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class CheckoutOrderPaginatedResultDto
{
    /**
     * @param  list<CheckoutOrderResultDto>  $items
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
                $items[] = CheckoutOrderResultDto::fromOrder($item);
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
     *     status:string|null,
     *     payment_status:string|null,
     *     shipment_status:string|null,
     *     currency:string,
     *     subtotal:float,
     *     discount_total:float,
     *     shipping_total:float,
     *     total:float,
     *     billing_address:array<string, mixed>|null,
     *     shipping_address:array<string, mixed>|null,
     *     items:list<array{
     *         product_variant_id:int|null,
     *         sku:string,
     *         name:string,
     *         quantity:int,
     *         unit_price:float,
     *         total_price:float
     *     }>,
     *     payments:list<array{
     *         gateway:string,
     *         transaction_id:string,
     *         status:string|null,
     *         amount:float
     *     }>,
     *     shipments:list<array{
     *         provider:string,
     *         tracking_number:string,
     *         status:string|null,
     *         cost:float
     *     }>,
     *     placed_at:string|null,
     *     created_at:string|null
     * }>
     */
    public function itemsToArray(): array
    {
        return array_map(
            static fn (CheckoutOrderResultDto $item): array => $item->toArray(),
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
