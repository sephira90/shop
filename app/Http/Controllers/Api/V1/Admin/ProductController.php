<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductIndexRequest;
use App\Http\Requests\Admin\ProductStoreRequest;
use App\Http\Requests\Admin\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\Admin\AdminCatalogService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly AdminCatalogService $adminCatalogService,
    ) {}

    /**
     * List products for admin panel.
     */
    public function index(ProductIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $products = $this->productRepository->paginateForAdmin($request->filter());

        return ApiResponse::paginated(ProductResource::collection($products->items()), $products);
    }

    /**
     * Create product.
     */
    public function store(ProductStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = $this->adminCatalogService->createProduct($request->validated());

        return ApiResponse::data(ProductResource::make($product), 201);
    }

    /**
     * Show single product.
     */
    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return ApiResponse::data(ProductResource::make($product->load(['category', 'variants.inventory'])));
    }

    /**
     * Update product.
     */
    public function update(ProductUpdateRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $this->adminCatalogService->updateProduct($product, $request->validated());

        return ApiResponse::data(ProductResource::make($product));
    }

    /**
     * Delete product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $this->adminCatalogService->deleteProduct($product);

        return ApiResponse::deleted();
    }
}
