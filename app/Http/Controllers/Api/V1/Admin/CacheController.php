<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Application\Admin\Cache\Commands\RefreshAdminCatalogCacheCommand;
use App\Application\Admin\Cache\Commands\RefreshAdminCatalogCacheHandler;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class CacheController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(
        private readonly RefreshAdminCatalogCacheHandler $refreshAdminCatalogCacheHandler,
    ) {}

    /**
     * Refresh storefront catalog cache.
     */
    public function refreshCatalog(): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $version = $this->refreshAdminCatalogCacheHandler->handle(
            new RefreshAdminCatalogCacheCommand
        );

        return ApiResponse::data([
            'catalog_version' => $version,
            'refreshed' => true,
        ]);
    }
}
