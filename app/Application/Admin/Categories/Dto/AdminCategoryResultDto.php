<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Dto;

use App\Models\Category;

final readonly class AdminCategoryResultDto
{
    public static function fromCategory(Category $category): self
    {
        $parent = null;
        if ($category->relationLoaded('parent')) {
            $loadedParent = $category->getRelation('parent');
            if ($loadedParent instanceof Category) {
                $parent = AdminCategoryParentResultDto::fromCategory($loadedParent);
            }
        }

        return new self(
            id: $category->id,
            parentId: $category->parent_id !== null ? (int) $category->parent_id : null,
            name: (string) $category->name,
            slug: (string) $category->slug,
            description: self::nullableString($category->description),
            metaTitle: self::nullableString($category->meta_title),
            metaDescription: self::nullableString($category->meta_description),
            isActive: (bool) $category->is_active,
            sortOrder: (int) $category->sort_order,
            parent: $parent,
            childrenCount: self::intAttribute($category, 'children_count'),
            productsCount: self::intAttribute($category, 'products_count'),
            createdAt: $category->created_at?->toJSON(),
            updatedAt: $category->updated_at?->toJSON(),
        );
    }

    public function __construct(
        public int $id,
        public ?int $parentId,
        public string $name,
        public string $slug,
        public ?string $description,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public bool $isActive,
        public int $sortOrder,
        public ?AdminCategoryParentResultDto $parent,
        public int $childrenCount,
        public int $productsCount,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    /**
     * @return array{
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
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parentId,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
            'parent' => $this->parent?->toArray(),
            'children_count' => $this->childrenCount,
            'products_count' => $this->productsCount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        return $value;
    }

    private static function intAttribute(Category $category, string $key): int
    {
        $value = $category->getAttribute($key);

        return is_numeric($value) ? (int) $value : 0;
    }
}
