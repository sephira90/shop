<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminCacheService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class CacheController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(private readonly AdminCacheService $adminCacheService) {}

    /**
     * Refresh storefront catalog cache.
     */
    public function refreshCatalog(): JsonResponse
    {
        $version = $this->adminCacheService->refreshCatalogCache();

        return ApiResponse::data([
            'catalog_version' => $version,
            'refreshed' => true,
        ]);
    }
}
