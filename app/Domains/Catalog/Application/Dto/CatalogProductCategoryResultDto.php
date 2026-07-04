<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Application\Dto;

use App\Models\Category;

final readonly class CatalogProductCategoryResultDto
{
    public static function fromCategory(Category $category): self
    {
        return new self(
            id: (int) $category->id,
            name: (string) $category->name,
            slug: (string) $category->slug,
        );
    }

    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
    ) {}

    /**
     * @return array{id:int,name:string,slug:string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
        ];
    }
}
