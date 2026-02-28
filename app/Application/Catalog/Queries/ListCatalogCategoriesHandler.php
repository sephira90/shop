<?php

declare(strict_types=1);

namespace App\Application\Catalog\Queries;

use App\Application\Catalog\Dto\CatalogCategoriesResultDto;
use App\Services\Catalog\CatalogService;

final class ListCatalogCategoriesHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly CatalogService $catalogService,
    ) {}

    /**
     * Execute catalog categories list query.
     */
    public function handle(): CatalogCategoriesResultDto
    {
        return CatalogCategoriesResultDto::fromCollection($this->catalogService->categories());
    }
}
