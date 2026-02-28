<?php

declare(strict_types=1);

namespace App\Application\Catalog\Dto;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class CatalogProductPaginatedResultDto
{
    /**
     * @param  list<CatalogProductResultDto>  $items
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
            if ($item instanceof Product) {
                $items[] = CatalogProductResultDto::fromProduct($item);
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
     *     sku:string,
     *     name:string,
     *     slug:string,
     *     short_description:string|null,
     *     description:string|null,
     *     status:string|null,
     *     is_featured:bool,
     *     brand:string|null,
     *     weight_grams:int|null,
     *     category:array{id:int,name:string,slug:string}|null,
     *     meta:array{title:string|null,description:string|null},
     *     variants:list<array{
     *         id:int,
     *         sku:string,
     *         name:string,
     *         attributes:array<string, mixed>|object|null,
     *         price:float,
     *         compare_at_price:float|null,
     *         currency:string,
     *         is_active:bool,
     *         inventory:array{
     *             quantity:int|null,
     *             reserved_quantity:int|null,
     *             available_quantity:int|null
     *         }
     *     }>,
     *     published_at:string|null,
     *     created_at:string|null,
     *     updated_at:string|null
     * }>
     */
    public function itemsToArray(): array
    {
        return array_map(
            static fn (CatalogProductResultDto $item): array => $item->toArray(),
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
