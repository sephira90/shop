<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ProductStatus;
use App\Filters\Admin\AdminProductListFilter;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class ProductRepository
{
    /**
     * List products for admin panel with typed filters.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForAdmin(AdminProductListFilter $filter): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['category', 'variants.inventory'])
            ->latest('id');

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

        return $query->paginate($filter->perPage, ['*'], 'page', $filter->page);
    }

    /**
     * Search products with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateCatalog(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $query = Product::query()
            ->select([
                'id',
                'sku',
                'name',
                'slug',
                'short_description',
                'description',
                'status',
                'is_featured',
                'category_id',
                'meta_title',
                'meta_description',
                'published_at',
                'created_at',
                'updated_at',
            ])
            ->with([
                'category:id,name,slug',
                'variants' => static function ($variantQuery): void {
                    $variantQuery
                        ->select([
                            'id',
                            'product_id',
                            'sku',
                            'name',
                            'attributes',
                            'price',
                            'compare_at_price',
                            'currency',
                            'is_active',
                        ])
                        ->where('is_active', true)
                        ->orderBy('id');
                },
                'variants.inventory:id,product_variant_id,quantity,reserved_quantity',
            ])
            ->where('status', ProductStatus::ACTIVE->value)
            ->whereHas('variants', static function (Builder $variantQuery): void {
                $variantQuery->where('is_active', true);
            })
            ->whereNotNull('published_at');

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get active product by slug.
     */
    public function findActiveBySlug(string $slug): ?Product
    {
        return Product::query()
            ->select([
                'id',
                'sku',
                'name',
                'slug',
                'short_description',
                'description',
                'status',
                'is_featured',
                'category_id',
                'meta_title',
                'meta_description',
                'published_at',
                'created_at',
                'updated_at',
            ])
            ->with([
                'category:id,name,slug',
                'variants' => static function ($variantQuery): void {
                    $variantQuery
                        ->select([
                            'id',
                            'product_id',
                            'sku',
                            'name',
                            'attributes',
                            'price',
                            'compare_at_price',
                            'currency',
                            'is_active',
                        ])
                        ->where('is_active', true)
                        ->orderBy('id');
                },
                'variants.inventory:id,product_variant_id,quantity,reserved_quantity',
            ])
            ->where('slug', $slug)
            ->where('status', ProductStatus::ACTIVE->value)
            ->whereHas('variants', static function (Builder $variantQuery): void {
                $variantQuery->where('is_active', true);
            })
            ->whereNotNull('published_at')
            ->first();
    }

    /**
     * Apply catalog filters to query.
     *
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['category_slug'])) {
            $query->whereHas('category', static function (Builder $categoryQuery) use ($filters): void {
                $categoryQuery->where('slug', (string) $filters['category_slug']);
            });
        }

        if (! empty($filters['q'])) {
            $search = trim((string) $filters['q']);
            $query->where(static function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%')
                    ->orWhere('short_description', 'like', '%'.$search.'%');
            });
        }

        if (! empty($filters['min_price']) || ! empty($filters['max_price'])) {
            $query->whereHas('variants', static function (Builder $variantQuery) use ($filters): void {
                $variantQuery->where('is_active', true);

                if (! empty($filters['min_price'])) {
                    $variantQuery->where('price', '>=', (float) $filters['min_price']);
                }

                if (! empty($filters['max_price'])) {
                    $variantQuery->where('price', '<=', (float) $filters['max_price']);
                }
            });
        }

        $sort = (string) ($filters['sort'] ?? 'newest');

        if (in_array($sort, ['price_asc', 'price_desc'], true)) {
            $query->withMin(
                ['variants as variants_min_price' => static function (Builder $variantQuery): void {
                    $variantQuery->where('is_active', true);
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
}
