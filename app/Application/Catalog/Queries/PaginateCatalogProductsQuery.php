<?php

declare(strict_types=1);

namespace App\Application\Catalog\Queries;

use App\Application\Catalog\Dto\CatalogProductListFilterDto;

final readonly class PaginateCatalogProductsQuery
{
    /**
     * Create query payload for catalog list.
     */
    public function __construct(
        public CatalogProductListFilterDto $filter,
        public int $perPage,
    ) {}
}
