<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Application\Catalog\Dto\CatalogProductListFilterDto;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Support\Observability\ObservabilityService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

final readonly class CatalogService
{
    /**
     * Create service instance.
     */
    public function __construct(
        private ProductRepository $productRepository,
        private CatalogVersionService $catalogVersionService,
        private ObservabilityService $observabilityService,
    ) {}

    /**
     * Return paginated catalog response with caching.
     */
    public function list(CatalogProductListFilterDto $filter, int $perPage = 12): LengthAwarePaginator
    {
        $cacheKey = sprintf(
            'catalog:v%d:list:%s',
            $this->catalogVersionService->current(),
            sha1(json_encode([$filter->toCachePayload(), $perPage], JSON_THROW_ON_ERROR)),
        );

        $cacheHit = Cache::has($cacheKey);
        $startedAt = hrtime(true);

        $paginator = Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            fn (): LengthAwarePaginator => $this->productRepository->paginateCatalog($filter, $perPage),
        );

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        $this->observabilityService->catalogCache('products_list', $cacheHit, $durationMs, count($paginator->items()));

        return $paginator;
    }

    /**
     * Return one active product by slug.
     */
    public function productBySlug(string $slug): ?Product
    {
        $cacheKey = sprintf('catalog:v%d:product:%s', $this->catalogVersionService->current(), $slug);
        $cacheHit = Cache::has($cacheKey);
        $startedAt = hrtime(true);

        $product = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            fn (): ?Product => $this->productRepository->findActiveBySlug($slug),
        );

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        $this->observabilityService->catalogCache('product_by_slug', $cacheHit, $durationMs, $product !== null ? 1 : 0);

        return $product;
    }

    /**
     * Return active categories list with cache.
     *
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        $cacheKey = sprintf('catalog:v%d:categories', $this->catalogVersionService->current());
        $cacheHit = Cache::has($cacheKey);
        $startedAt = hrtime(true);

        $categories = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            static fn (): Collection => Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'parent_id', 'name', 'slug', 'meta_title', 'meta_description']),
        );

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        $this->observabilityService->catalogCache('categories', $cacheHit, $durationMs, $categories->count());

        return $categories;
    }
}
