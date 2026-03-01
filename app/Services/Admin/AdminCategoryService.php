<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Application\Admin\Categories\Dto\CreateAdminCategoryInputDto;
use App\Application\Admin\Categories\Dto\UpdateAdminCategoryInputDto;
use App\Models\Category;
use App\Services\Catalog\CatalogVersionService;

final class AdminCategoryService
{
    /**
     * Create service instance.
     */
    public function __construct(
        private readonly CatalogVersionService $catalogVersionService,
    ) {}

    /**
     * Create category.
     */
    public function create(CreateAdminCategoryInputDto $input): Category
    {
        $category = Category::query()->create($input->toPersistenceAttributes());
        $this->catalogVersionService->bump();

        return $this->freshCategory($category);
    }

    /**
     * Update category.
     */
    public function update(Category $category, UpdateAdminCategoryInputDto $input): Category
    {
        $category->update($input->toPersistenceAttributes());
        $this->catalogVersionService->bump();

        return $this->freshCategory($category);
    }

    /**
     * Delete category and invalidate catalog cache.
     */
    public function delete(Category $category): void
    {
        $category->delete();
        $this->catalogVersionService->bump();
    }

    /**
     * Reload category with admin relations.
     */
    private function freshCategory(Category $category): Category
    {
        return $category
            ->refresh()
            ->load([
                'parent:id,name,slug',
            ])
            ->loadCount(['children', 'products']);
    }
}
