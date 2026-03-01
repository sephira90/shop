<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Application\Admin\Products\Dto\AdminProductListFilterDto;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;

final class AdminProductReadRepository
{
    /**
     * List products for admin panel with typed filters.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForAdmin(AdminProductListFilterDto $filter): LengthAwarePaginator
    {
        $query = Product::query()
            ->with($this->adminRelations())
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
     * Load the canonical admin detail read-model shape for a product.
     */
    public function loadForAdmin(Product $product): Product
    {
        return $product->load($this->adminRelations());
    }

    /**
     * Resolve admin product relations with deterministic variant ordering.
     *
     * @return array{0:string, variants:\Closure(Relation<*, *, *>): mixed}
     */
    private function adminRelations(): array
    {
        return [
            'category:id,name,slug',
            'variants' => static function ($variantQuery): void {
                if ($variantQuery instanceof Relation) {
                    $variantQuery = $variantQuery->getQuery();
                }

                if (! $variantQuery instanceof Builder) {
                    return;
                }

                $variantQuery
                    ->orderBy('id')
                    ->with(['inventory:id,product_variant_id,quantity,reserved_quantity,low_stock_threshold']);
            },
        ];
    }
}
