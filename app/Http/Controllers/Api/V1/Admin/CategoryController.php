<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryIndexRequest;
use App\Http\Requests\Admin\CategoryStoreRequest;
use App\Http\Requests\Admin\CategoryUpdateRequest;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\Admin\AdminCategoryService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly AdminCategoryService $adminCategoryService,
    ) {}

    /**
     * List categories for admin panel.
     */
    public function index(CategoryIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);
        $categories = $this->categoryRepository->paginateForAdmin($request->filter());

        return ApiResponse::paginated($categories->items(), $categories);
    }

    /**
     * Create category.
     */
    public function store(CategoryStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $category = $this->adminCategoryService->create($request->validated());

        return ApiResponse::data($category, 201);
    }

    /**
     * Show single category.
     */
    public function show(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        return ApiResponse::data($category->load(['parent:id,name,slug'])->loadCount(['children', 'products']));
    }

    /**
     * Update category.
     */
    public function update(CategoryUpdateRequest $request, Category $category): JsonResponse
    {
        $this->authorize('update', $category);

        $updated = $this->adminCategoryService->update($category, $request->validated());

        return ApiResponse::data($updated);
    }

    /**
     * Delete category.
     */
    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $this->adminCategoryService->delete($category);

        return ApiResponse::deleted();
    }
}
