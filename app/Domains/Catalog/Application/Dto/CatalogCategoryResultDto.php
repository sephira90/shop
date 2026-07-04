<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Application\Dto;

use App\Models\Category;

final readonly class CatalogCategoryResultDto
{
    public static function fromCategory(Category $category): self
    {
        return new self(
            id: (int) $category->id,
            parentId: is_numeric($category->parent_id) ? (int) $category->parent_id : null,
            name: (string) $category->name,
            slug: (string) $category->slug,
            metaTitle: is_string($category->meta_title) ? $category->meta_title : null,
            metaDescription: is_string($category->meta_description) ? $category->meta_description : null,
        );
    }

    public function __construct(
        public int $id,
        public ?int $parentId,
        public string $name,
        public string $slug,
        public ?string $metaTitle,
        public ?string $metaDescription,
    ) {}

    /**
     * @return array{
     *     id:int,
     *     parent_id:int|null,
     *     name:string,
     *     slug:string,
     *     meta_title:string|null,
     *     meta_description:string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parentId,
            'name' => $this->name,
            'slug' => $this->slug,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
        ];
    }
}
