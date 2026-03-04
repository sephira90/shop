<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Contracts;

use App\Application\Admin\Products\Dto\AdminProductListFilterDto;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

interface AdminProductReadRepository
{
    /**
     * List products for admin panel with typed filters.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForAdmin(AdminProductListFilterDto $filter): LengthAwarePaginator;

    /**
     * Load the canonical admin detail read-model shape for a product.
     */
    public function loadForAdmin(Product $product): Product;
}
