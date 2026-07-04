<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Contracts;

use App\Domains\Catalog\Contracts\Dto\CatalogProductListFilterDto;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

interface CatalogProductReadRepository
{
    /**
     * Search products with filters.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateCatalog(CatalogProductListFilterDto $filter, int $perPage = 12): LengthAwarePaginator;

    /**
     * Get active product by slug.
     */
    public function findActiveBySlug(string $slug): ?Product;
}
