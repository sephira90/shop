<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Application\Queries;

use App\Domains\Catalog\Contracts\Dto\CatalogProductListFilterDto;

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
