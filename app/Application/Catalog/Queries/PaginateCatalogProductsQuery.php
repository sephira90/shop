<?php

declare(strict_types=1);

namespace App\Application\Catalog\Queries;

final readonly class PaginateCatalogProductsQuery
{
    /**
     * Create query payload for catalog list.
     *
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public array $filters,
        public int $perPage,
    ) {}
}
