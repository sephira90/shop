<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Catalog\Queries\GetCatalogProductBySlugHandler;
use App\Application\Catalog\Queries\GetCatalogProductBySlugQuery;
use App\Application\Catalog\Queries\ListCatalogCategoriesHandler;
use App\Application\Catalog\Queries\PaginateCatalogProductsHandler;
use App\Application\Catalog\Queries\PaginateCatalogProductsQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\CatalogIndexRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CatalogController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(
        private readonly PaginateCatalogProductsHandler $paginateCatalogProductsHandler,
        private readonly GetCatalogProductBySlugHandler $getCatalogProductBySlugHandler,
        private readonly ListCatalogCategoriesHandler $listCatalogCategoriesHandler,
    ) {}

    /**
     * Return catalog list with filters.
     */
    public function index(CatalogIndexRequest $request): JsonResponse
    {
        $perPage = $request->perPage();
        $filter = $request->filter();
        $paginated = $this->paginateCatalogProductsHandler->handle(
            new PaginateCatalogProductsQuery($filter, $perPage)
        );

        return ApiResponse::paginatedWithMeta($paginated->itemsToArray(), $paginated->metaToArray());
    }

    /**
     * Return one product by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $product = $this->getCatalogProductBySlugHandler->handle(
            new GetCatalogProductBySlugQuery($slug)
        );

        if ($product === null) {
            throw new NotFoundHttpException('Product not found.');
        }

        return ApiResponse::data($product->toArray());
    }

    /**
     * Return active categories.
     */
    public function categories(): JsonResponse
    {
        $categories = $this->listCatalogCategoriesHandler->handle();

        return ApiResponse::data($categories->itemsToArray());
    }
}
