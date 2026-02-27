<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Application\Catalog\Dto\CatalogProductListFilterDto;
use App\Enums\ProductStatus;
use App\Filters\Admin\AdminProductListFilter;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

final class ProductRepository
{
    /**
     * @var list<string>
     */
    private const CATALOG_PRODUCT_COLUMNS = [
        'id',
        'sku',
        'name',
        'slug',
        'short_description',
        'description',
        'status',
        'is_featured',
        'category_id',
        'brand',
        'weight_grams',
        'meta_title',
        'meta_description',
        'published_at',
        'created_at',
        'updated_at',
    ];

    /**
     * @var list<string>
     */
    private const CATALOG_VARIANT_COLUMNS = [
        'id',
        'product_id',
        'sku',
        'name',
        'attributes',
        'price',
        'compare_at_price',
        'currency',
        'is_active',
    ];

    /**
     * List products for admin panel with typed filters.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForAdmin(AdminProductListFilter $filter): LengthAwarePaginator
    {
        $query = $this->newAdminListQuery();
        $this->applyAdminFilters($query, $filter);

        return $query->paginate($filter->perPage, ['*'], 'page', $filter->page);
    }

    /**
     * Search products with filters.
     */
    public function paginateCatalog(CatalogProductListFilterDto $filter, int $perPage = 12): LengthAwarePaginator
    {
        $query = $this->newCatalogBaseQuery();

        $this->applyFilters($query, $filter);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get active product by slug.
     */
    public function findActiveBySlug(string $slug): ?Product
    {
        return $this->newCatalogBaseQuery()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Apply catalog filters to query.
     */
    private function applyFilters(Builder $query, CatalogProductListFilterDto $filter): void
    {
        if ($filter->categorySlug !== null) {
            $query->whereHas('category', function (Builder $categoryQuery) use ($filter): void {
                $categoryQuery->where('slug', $filter->categorySlug);
            });
        }

        if ($filter->search !== null) {
            $query->where(function (Builder $searchQuery) use ($filter): void {
                $searchQuery
                    ->where('name', 'like', '%'.$filter->search.'%')
                    ->orWhere('sku', 'like', '%'.$filter->search.'%')
                    ->orWhere('short_description', 'like', '%'.$filter->search.'%');
            });
        }

        if ($filter->minPrice !== null || $filter->maxPrice !== null) {
            $query->whereHas('variants', function (Builder $variantQuery) use ($filter): void {
                $variantQuery->where('is_active', true);

                if ($filter->minPrice !== null) {
                    $variantQuery->where('price', '>=', $filter->minPrice);
                }

                if ($filter->maxPrice !== null) {
                    $variantQuery->where('price', '<=', $filter->maxPrice);
                }
            });
        }

        $sort = $filter->sort;

        if (in_array($sort, ['price_asc', 'price_desc'], true)) {
            $query->withMin(
                ['variants as variants_min_price' => static function (Builder $variantQuery): void {
                    self::applyActiveVariantFilter($variantQuery);
                }],
                'price',
            );
        }

        match ($sort) {
            'price_asc' => $query->orderBy('variants_min_price'),
            'price_desc' => $query->orderByDesc('variants_min_price'),
            'name_asc' => $query->orderBy('name'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };
    }

    /**
     * Build admin list base query with eager-loaded relations.
     *
     * @return Builder<Product>
     */
    private function newAdminListQuery(): Builder
    {
        return Product::query()
            ->with(['category', 'variants.inventory'])
            ->latest('id');
    }

    /**
     * Apply typed admin filters to product query.
     *
     * @param  Builder<Product>  $query
     */
    private function applyAdminFilters(Builder $query, AdminProductListFilter $filter): void
    {
        if ($filter->search !== null) {
            $like = '%'.$filter->search.'%';

            $query->where(static function (Builder $builder) use ($like): void {
                $builder
                    ->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            });
        }

        if ($filter->status !== null) {
            $query->where('status', $filter->status->value);
        }

        if ($filter->categoryId !== null) {
            $query->where('category_id', $filter->categoryId);
        }
    }

    /**
     * Build reusable catalog query-shape for list and show paths.
     *
     * @return Builder<Product>
     */
    private function newCatalogBaseQuery(): Builder
    {
        return Product::query()
            ->select(self::CATALOG_PRODUCT_COLUMNS)
            ->with($this->catalogWithRelations())
            ->where('status', ProductStatus::ACTIVE->value)
            ->whereHas('variants', static function (Builder $variantQuery): void {
                self::applyActiveVariantFilter($variantQuery);
            })
            ->whereNotNull('published_at');
    }

    /**
     * Resolve catalog eager-load configuration.
     *
     * @return array<int|string, mixed>
     */
    private function catalogWithRelations(): array
    {
        return [
            'category:id,name,slug',
            'variants' => static function ($variantQuery): void {
                self::applyCatalogVariantProjection($variantQuery);
            },
            'variants.inventory:id,product_variant_id,quantity,reserved_quantity',
        ];
    }

    /**
     * Apply active variant filter.
     *
     * @param  Builder<Model>  $variantQuery
     */
    private static function applyActiveVariantFilter(Builder $variantQuery): void
    {
        $variantQuery->where('is_active', true);
    }

    /**
     * Apply variant projection used by catalog list/show responses.
     *
     * @param  Builder<ProductVariant>|Relation<ProductVariant, Product, mixed>  $variantQuery
     */
    private static function applyCatalogVariantProjection(Builder|Relation $variantQuery): void
    {
        if ($variantQuery instanceof Relation) {
            $variantQuery = $variantQuery->getQuery();
        }

        $variantQuery
            ->select(self::CATALOG_VARIANT_COLUMNS)
            ->where('is_active', true)
            ->orderBy('id');
    }
}
