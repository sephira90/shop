<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Application\Queries;

use App\Domains\Catalog\Application\Dto\CatalogProductPaginatedResultDto;
use App\Domains\Catalog\Contracts\CatalogReadService;

final class PaginateCatalogProductsHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly CatalogReadService $catalogService,
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
