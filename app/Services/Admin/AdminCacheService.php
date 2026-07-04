<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Domains\Catalog\Contracts\CatalogCacheVersion;

final class AdminCacheService
{
    /**
     * Create service instance.
     */
    public function __construct(private readonly CatalogCacheVersion $catalogVersionService) {}

    /**
     * Bump catalog cache version and return new value.
     */
    public function refreshCatalogCache(): int
    {
        return $this->catalogVersionService->bump();
    }
}
