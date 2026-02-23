<?php

declare(strict_types=1);

namespace App\Application\Catalog\Queries;

use App\Models\Product;
use App\Services\Catalog\CatalogService;

final class GetCatalogProductBySlugHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly CatalogService $catalogService,
    ) {}

    /**
     * Execute catalog product show query.
     */
    public function handle(GetCatalogProductBySlugQuery $query): ?Product
    {
        return $this->catalogService->productBySlug($query->slug);
    }
}
