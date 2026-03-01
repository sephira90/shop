<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Dto;

use App\Models\Category;

final readonly class AdminCategoryOptionResultDto
{
    public function __construct(
        public int $id,
        public ?int $parentId,
        public string $name,
        public string $slug,
        public bool $isActive,
    ) {}

    public static function fromCategory(Category $category): self
    {
        return new self(
            id: $category->id,
            parentId: $category->parent_id !== null ? (int) $category->parent_id : null,
            name: (string) $category->name,
            slug: (string) $category->slug,
            isActive: (bool) $category->is_active,
        );
    }

    /**
     * @return array{
     *     id:int,
     *     parent_id:int|null,
     *     name:string,
     *     slug:string,
     *     is_active:bool
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parentId,
            'name' => $this->name,
            'slug' => $this->slug,
            'is_active' => $this->isActive,
        ];
    }
}
