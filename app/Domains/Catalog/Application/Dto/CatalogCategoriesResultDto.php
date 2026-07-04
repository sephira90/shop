<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Application\Dto;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

final readonly class CatalogCategoriesResultDto
{
    /**
     * @param  list<CatalogCategoryResultDto>  $items
     */
    public function __construct(
        public array $items,
    ) {}

    /**
     * @param  Collection<int, Category>  $categories
     */
    public static function fromCollection(Collection $categories): self
    {
        $items = [];
        foreach ($categories as $category) {
            $items[] = CatalogCategoryResultDto::fromCategory($category);
        }

        return new self($items);
    }

    /**
     * @return list<array{
     *     id:int,
     *     parent_id:int|null,
     *     name:string,
     *     slug:string,
     *     meta_title:string|null,
     *     meta_description:string|null
     * }>
     */
    public function itemsToArray(): array
    {
        return array_map(
            static fn (CatalogCategoryResultDto $item): array => $item->toArray(),
            $this->items
        );
    }
}
