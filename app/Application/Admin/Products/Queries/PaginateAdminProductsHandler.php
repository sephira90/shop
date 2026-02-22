<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Queries;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PaginateAdminProductsHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    /**
     * Execute admin products list query.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function handle(PaginateAdminProductsQuery $query): LengthAwarePaginator
    {
        return $this->productRepository->paginateForAdmin($query->filter);
    }
}
