<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Catalog\CatalogVersionService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CatalogVersionServiceTest extends TestCase
{
    /**
     * Ensure service returns default version when key is absent.
     */
    public function test_it_returns_default_version_when_cache_key_is_missing(): void
    {
        Cache::forget('catalog:version');

        $service = app(CatalogVersionService::class);

        $this->assertSame(1, $service->current());
    }

    /**
     * Ensure service increments and persists catalog version.
     */
    public function test_it_bumps_and_persists_catalog_version(): void
    {
        Cache::forever('catalog:version', 7);

        $service = app(CatalogVersionService::class);

        $nextVersion = $service->bump();

        $this->assertSame(8, $nextVersion);
        $this->assertSame(8, (int) Cache::get('catalog:version'));
    }

    /**
     * Ensure bump initializes missing key and stores next version.
     */
    public function test_it_bumps_from_default_when_cache_key_is_missing(): void
    {
        Cache::forget('catalog:version');

        $service = app(CatalogVersionService::class);

        $nextVersion = $service->bump();

        $this->assertSame(2, $nextVersion);
        $this->assertSame(2, (int) Cache::get('catalog:version'));
    }

    /**
     * Ensure repeated bumps are monotonic.
     */
    public function test_it_bumps_monotonically(): void
    {
        Cache::forever('catalog:version', 3);

        $service = app(CatalogVersionService::class);

        $this->assertSame(4, $service->bump());
        $this->assertSame(5, $service->bump());
        $this->assertSame(5, (int) Cache::get('catalog:version'));
    }
}
