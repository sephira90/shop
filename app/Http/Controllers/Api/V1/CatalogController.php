<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Catalog\Dto\CatalogProductListFilterDto;
use App\Application\Catalog\Queries\GetCatalogProductBySlugHandler;
use App\Application\Catalog\Queries\GetCatalogProductBySlugQuery;
use App\Application\Catalog\Queries\ListCatalogCategoriesHandler;
use App\Application\Catalog\Queries\PaginateCatalogProductsHandler;
use App\Application\Catalog\Queries\PaginateCatalogProductsQuery;
use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category_slug' => ['nullable', 'string', 'max:180'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'in:newest,price_asc,price_desc,name_asc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 12);
        $filter = CatalogProductListFilterDto::fromValidated($validated);
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
            return ApiResponse::error('Product not found.', Response::HTTP_NOT_FOUND);
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
