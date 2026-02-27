<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Dto;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class AdminCategoryPaginatedResultDto
{
    /**
     * @param  list<AdminCategoryResultDto>  $items
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
            if ($item instanceof Category) {
                $items[] = AdminCategoryResultDto::fromCategory($item);
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
     *     parent_id:int|null,
     *     name:string,
     *     slug:string,
     *     description:string|null,
     *     meta_title:string|null,
     *     meta_description:string|null,
     *     is_active:bool,
     *     sort_order:int,
     *     parent:array{id:int,name:string,slug:string}|null,
     *     children_count:int,
     *     products_count:int,
     *     created_at:string|null,
     *     updated_at:string|null
     * }>
     */
    public function itemsToArray(): array
    {
        return array_map(
            static fn (AdminCategoryResultDto $item): array => $item->toArray(),
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
