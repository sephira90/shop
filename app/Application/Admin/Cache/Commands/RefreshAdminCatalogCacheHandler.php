<?php

declare(strict_types=1);

namespace App\Application\Admin\Cache\Commands;

use App\Services\Admin\AdminCacheService;

final class RefreshAdminCatalogCacheHandler
{
    /**
     * Create command handler instance.
     */
    public function __construct(
        private readonly AdminCacheService $adminCacheService,
    ) {}

    /**
     * Execute admin catalog cache refresh command.
     */
    public function handle(RefreshAdminCatalogCacheCommand $_command): int
    {
        return $this->adminCacheService->refreshCatalogCache();
    }
}
