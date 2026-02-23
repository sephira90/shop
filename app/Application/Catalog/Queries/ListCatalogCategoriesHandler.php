<?php

declare(strict_types=1);

namespace App\Application\Catalog\Queries;

use App\Models\Category;
use App\Services\Catalog\CatalogService;
use Illuminate\Database\Eloquent\Collection;

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
     *
     * @return Collection<int, Category>
     */
    public function handle(): Collection
    {
        return $this->catalogService->categories();
    }
}
