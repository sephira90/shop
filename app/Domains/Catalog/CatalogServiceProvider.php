<?php

declare(strict_types=1);

namespace App\Domains\Catalog;

use App\Domains\Catalog\Contracts\CatalogCacheVersion;
use App\Domains\Catalog\Contracts\CatalogProductReadRepository;
use App\Domains\Catalog\Contracts\CatalogReadService;
use App\Domains\Catalog\Repositories\CatalogProductReadRepository as CatalogProductReadRepositoryImplementation;
use App\Domains\Catalog\Services\CatalogService;
use App\Domains\Catalog\Services\CatalogVersionService;
use Illuminate\Support\ServiceProvider;

final class CatalogServiceProvider extends ServiceProvider
{
    /**
     * Register Catalog module contracts.
     */
    public function register(): void
    {
        $this->app->bind(CatalogProductReadRepository::class, CatalogProductReadRepositoryImplementation::class);
        $this->app->bind(CatalogCacheVersion::class, CatalogVersionService::class);
        $this->app->bind(CatalogReadService::class, CatalogService::class);
    }
}
