<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Contracts;

use App\Domains\Catalog\Contracts\Dto\CatalogProductListFilterDto;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Catalog read service contract.
 *
 * Public read surface consumed by catalog query handlers and by
 * performance-smoke scenarios measuring service-level query budgets.
 */
interface CatalogReadService
{
    /**
     * Return paginated catalog response with caching.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function list(CatalogProductListFilterDto $filter, int $perPage = 12): LengthAwarePaginator;

    /**
     * Return one active product by slug.
     */
    public function productBySlug(string $slug): ?Product;

    /**
     * Return active categories list with cache.
     *
     * @return Collection<int, Category>
     */
    public function categories(): Collection;
}
