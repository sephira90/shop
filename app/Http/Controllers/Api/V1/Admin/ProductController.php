<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Application\Admin\Products\Commands\CreateAdminProductCommand;
use App\Application\Admin\Products\Commands\CreateAdminProductHandler;
use App\Application\Admin\Products\Commands\DeleteAdminProductCommand;
use App\Application\Admin\Products\Commands\DeleteAdminProductHandler;
use App\Application\Admin\Products\Commands\UpdateAdminProductCommand;
use App\Application\Admin\Products\Commands\UpdateAdminProductHandler;
use App\Application\Admin\Products\Queries\GetAdminProductDetailHandler;
use App\Application\Admin\Products\Queries\GetAdminProductDetailQuery;
use App\Application\Admin\Products\Queries\PaginateAdminProductsHandler;
use App\Application\Admin\Products\Queries\PaginateAdminProductsQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductIndexRequest;
use App\Http\Requests\Admin\ProductStoreRequest;
use App\Http\Requests\Admin\ProductUpdateRequest;
use App\Models\Product;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(
        private readonly PaginateAdminProductsHandler $paginateAdminProductsHandler,
        private readonly GetAdminProductDetailHandler $getAdminProductDetailHandler,
        private readonly CreateAdminProductHandler $createAdminProductHandler,
        private readonly UpdateAdminProductHandler $updateAdminProductHandler,
        private readonly DeleteAdminProductHandler $deleteAdminProductHandler,
    ) {}

    /**
     * List products for admin panel.
     */
    public function index(ProductIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $products = $this->paginateAdminProductsHandler->handle(
            new PaginateAdminProductsQuery($request->filter())
        );

        return ApiResponse::paginatedWithMeta($products->itemsToArray(), $products->metaToArray());
    }

    /**
     * Create product.
     */
    public function store(ProductStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = $this->createAdminProductHandler->handle(
            new CreateAdminProductCommand($request->toDto())
        );

        return ApiResponse::data($product->toArray(), 201);
    }

    /**
     * Show single product.
     */
    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $detail = $this->getAdminProductDetailHandler->handle(new GetAdminProductDetailQuery($product));

        return ApiResponse::data($detail->toArray());
    }

    /**
     * Update product.
     */
    public function update(ProductUpdateRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product = $this->updateAdminProductHandler->handle(
            new UpdateAdminProductCommand($product, $request->toDto())
        );

        return ApiResponse::data($product->toArray());
    }

    /**
     * Delete product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $this->deleteAdminProductHandler->handle(new DeleteAdminProductCommand($product));

        return ApiResponse::deleted();
    }
}
