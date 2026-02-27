<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Application\Admin\Categories\Commands\CreateAdminCategoryCommand;
use App\Application\Admin\Categories\Commands\CreateAdminCategoryHandler;
use App\Application\Admin\Categories\Commands\DeleteAdminCategoryCommand;
use App\Application\Admin\Categories\Commands\DeleteAdminCategoryHandler;
use App\Application\Admin\Categories\Commands\UpdateAdminCategoryCommand;
use App\Application\Admin\Categories\Commands\UpdateAdminCategoryHandler;
use App\Application\Admin\Categories\Queries\GetAdminCategoryDetailHandler;
use App\Application\Admin\Categories\Queries\GetAdminCategoryDetailQuery;
use App\Application\Admin\Categories\Queries\PaginateAdminCategoriesHandler;
use App\Application\Admin\Categories\Queries\PaginateAdminCategoriesQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryIndexRequest;
use App\Http\Requests\Admin\CategoryStoreRequest;
use App\Http\Requests\Admin\CategoryUpdateRequest;
use App\Models\Category;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(
        private readonly PaginateAdminCategoriesHandler $paginateAdminCategoriesHandler,
        private readonly GetAdminCategoryDetailHandler $getAdminCategoryDetailHandler,
        private readonly CreateAdminCategoryHandler $createAdminCategoryHandler,
        private readonly UpdateAdminCategoryHandler $updateAdminCategoryHandler,
        private readonly DeleteAdminCategoryHandler $deleteAdminCategoryHandler,
    ) {}

    /**
     * List categories for admin panel.
     */
    public function index(CategoryIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);
        $categories = $this->paginateAdminCategoriesHandler->handle(
            new PaginateAdminCategoriesQuery($request->filter())
        );

        return ApiResponse::paginated($categories->items(), $categories);
    }

    /**
     * Create category.
     */
    public function store(CategoryStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $category = $this->createAdminCategoryHandler->handle(
            new CreateAdminCategoryCommand($request->toDto())
        );

        return ApiResponse::data($category, 201);
    }

    /**
     * Show single category.
     */
    public function show(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        $detail = $this->getAdminCategoryDetailHandler->handle(
            new GetAdminCategoryDetailQuery($category)
        );

        return ApiResponse::data($detail);
    }

    /**
     * Update category.
     */
    public function update(CategoryUpdateRequest $request, Category $category): JsonResponse
    {
        $this->authorize('update', $category);

        $updated = $this->updateAdminCategoryHandler->handle(
            new UpdateAdminCategoryCommand($category, $request->toDto())
        );

        return ApiResponse::data($updated);
    }

    /**
     * Delete category.
     */
    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $this->deleteAdminCategoryHandler->handle(new DeleteAdminCategoryCommand($category));

        return ApiResponse::deleted();
    }
}
