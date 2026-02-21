<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Services\Catalog\CatalogVersionService;

final class AdminCacheService
{
    /**
     * Create service instance.
     */
    public function __construct(private readonly CatalogVersionService $catalogVersionService) {}

    /**
     * Bump catalog cache version and return new value.
     */
    public function refreshCatalogCache(): int
    {
        return $this->catalogVersionService->bump();
    }
}
