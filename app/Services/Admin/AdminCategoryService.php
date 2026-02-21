<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Category;
use App\Services\Catalog\CatalogVersionService;
use Illuminate\Support\Str;

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
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): Category
    {
        $payload['slug'] = $payload['slug'] ?? Str::slug((string) $payload['name']);
        $payload['is_active'] = (bool) ($payload['is_active'] ?? true);
        $payload['sort_order'] = (int) ($payload['sort_order'] ?? 0);

        $category = Category::query()->create($payload);
        $this->catalogVersionService->bump();

        return $this->freshCategory($category);
    }

    /**
     * Update category.
     *
     * @param  array<string, mixed>  $payload
     */
    public function update(Category $category, array $payload): Category
    {
        $payload['slug'] = $payload['slug'] ?? $category->slug;
        $payload['is_active'] = (bool) ($payload['is_active'] ?? false);
        $payload['sort_order'] = (int) ($payload['sort_order'] ?? 0);

        $category->update($payload);
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
        return $category->fresh([
            'parent:id,name,slug',
        ])->loadCount(['children', 'products']);
    }
}
