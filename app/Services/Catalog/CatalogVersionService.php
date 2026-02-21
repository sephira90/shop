<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use Illuminate\Support\Facades\Cache;

final class CatalogVersionService
{
    private const CACHE_KEY = 'catalog:version';

    private const DEFAULT_VERSION = 1;

    /**
     * Resolve current catalog cache version.
     */
    public function current(): int
    {
        return (int) Cache::get(self::CACHE_KEY, self::DEFAULT_VERSION);
    }

    /**
     * Increment catalog cache version and return new value.
     */
    public function bump(): int
    {
        // Ensure key exists and use atomic increment to avoid lost updates.
        Cache::add(self::CACHE_KEY, self::DEFAULT_VERSION);

        $nextVersion = Cache::increment(self::CACHE_KEY);

        if (! is_int($nextVersion)) {
            $nextVersion = $this->current() + 1;
            Cache::forever(self::CACHE_KEY, $nextVersion);
        }

        return $nextVersion;
    }
}
