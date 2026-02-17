<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class ProductRepository
{
    /**
     * Search products with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateCatalog(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['category', 'variants.inventory'])
            ->where('status', ProductStatus::ACTIVE->value)
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
            ->with(['category', 'variants.inventory'])
            ->where('slug', $slug)
            ->where('status', ProductStatus::ACTIVE->value)
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
            $query->withMin('variants', 'price');
        }

        match ($sort) {
            'price_asc' => $query->orderBy('variants_min_price'),
            'price_desc' => $query->orderByDesc('variants_min_price'),
            'name_asc' => $query->orderBy('name'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };
    }
}
