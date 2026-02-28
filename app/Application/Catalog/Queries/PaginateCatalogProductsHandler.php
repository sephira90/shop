<?php

declare(strict_types=1);

namespace App\Application\Catalog\Queries;

use App\Application\Catalog\Dto\CatalogProductPaginatedResultDto;
use App\Services\Catalog\CatalogService;

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
     */
    public function handle(PaginateCatalogProductsQuery $query): CatalogProductPaginatedResultDto
    {
        return CatalogProductPaginatedResultDto::fromPaginator(
            $this->catalogService->list($query->filter, $query->perPage)
        );
    }
}
