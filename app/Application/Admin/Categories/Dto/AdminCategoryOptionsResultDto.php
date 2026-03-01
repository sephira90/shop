<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Dto;

use App\Models\Category;
use Illuminate\Support\Collection;

final readonly class AdminCategoryOptionsResultDto
{
    /**
     * @param  list<AdminCategoryOptionResultDto>  $items
     */
    public function __construct(
        public array $items,
    ) {}

    /**
     * @param  Collection<int, Category>  $categories
     */
    public static function fromCategories(Collection $categories): self
    {
        $items = [];

        foreach ($categories as $category) {
            $items[] = AdminCategoryOptionResultDto::fromCategory($category);
        }

        return new self($items);
    }

    /**
     * @return list<array{
     *     id:int,
     *     parent_id:int|null,
     *     name:string,
     *     slug:string,
     *     is_active:bool
     * }>
     */
    public function itemsToArray(): array
    {
        return array_map(
            static fn (AdminCategoryOptionResultDto $item): array => $item->toArray(),
            $this->items,
        );
    }
}
