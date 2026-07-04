<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Application\Queries;

use App\Domains\Catalog\Application\Dto\CatalogProductResultDto;
use App\Domains\Catalog\Contracts\CatalogReadService;

final class GetCatalogProductBySlugHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly CatalogReadService $catalogService,
    ) {}

    /**
     * Execute catalog product show query.
     */
    public function handle(GetCatalogProductBySlugQuery $query): ?CatalogProductResultDto
    {
        $product = $this->catalogService->productBySlug($query->slug);

        if ($product === null) {
            return null;
        }

        return CatalogProductResultDto::fromProduct($product);
    }
}
