<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Application\Queries;

use App\Domains\Catalog\Application\Dto\CatalogCategoriesResultDto;
use App\Domains\Catalog\Contracts\CatalogReadService;

final class ListCatalogCategoriesHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly CatalogReadService $catalogService,
    ) {}

    /**
     * Execute catalog categories list query.
     */
    public function handle(): CatalogCategoriesResultDto
    {
        return CatalogCategoriesResultDto::fromCollection($this->catalogService->categories());
    }
}
