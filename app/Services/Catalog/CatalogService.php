<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

final readonly class CatalogService
{
    /**
     * Create service instance.
     */
    public function __construct(private ProductRepository $productRepository) {}

    /**
     * Return paginated catalog response with caching.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $cacheKey = sprintf(
            'catalog:v%d:list:%s',
            $this->catalogVersion(),
            sha1(json_encode([$filters, $perPage], JSON_THROW_ON_ERROR)),
        );

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            fn (): LengthAwarePaginator => $this->productRepository->paginateCatalog($filters, $perPage),
        );
    }

    /**
     * Return one active product by slug.
     */
    public function productBySlug(string $slug): ?Product
    {
        return Cache::remember(
            sprintf('catalog:v%d:product:%s', $this->catalogVersion(), $slug),
            now()->addMinutes(10),
            fn (): ?Product => $this->productRepository->findActiveBySlug($slug),
        );
    }

    /**
     * Resolve catalog cache version.
     */
    private function catalogVersion(): int
    {
        return (int) Cache::get('catalog:version', 1);
    }
}
