<?php

declare(strict_types=1);

namespace App\Application\Catalog\Queries;

use App\Models\Product;
use App\Services\Catalog\CatalogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PaginateCatalogProductsHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly CatalogService $catalogService,
    ) {}

    /**
     * Execute catalog products pagination query.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function handle(PaginateCatalogProductsQuery $query): LengthAwarePaginator
    {
        return $this->catalogService->list($query->filters, $query->perPage);
    }
}
