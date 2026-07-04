<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Controllers;

use App\Domains\Catalog\Application\Queries\GetCatalogProductBySlugHandler;
use App\Domains\Catalog\Application\Queries\GetCatalogProductBySlugQuery;
use App\Domains\Catalog\Application\Queries\ListCatalogCategoriesHandler;
use App\Domains\Catalog\Application\Queries\PaginateCatalogProductsHandler;
use App\Domains\Catalog\Application\Queries\PaginateCatalogProductsQuery;
use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CatalogController extends Controller
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
