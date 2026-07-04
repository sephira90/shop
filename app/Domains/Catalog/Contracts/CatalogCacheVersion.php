<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Contracts;

/**
 * Catalog cache-version contract.
 *
 * Stable operational contract: the cache key `catalog:version` and the
 * current/bump semantics are shared between catalog reads (cache-key
 * versioning) and admin writes (cache invalidation on every mutation).
 */
interface CatalogCacheVersion
{
    /**
     * Current catalog cache version.
     */
    public function current(): int;

    /**
     * Bump the catalog cache version and return the new value.
     */
    public function bump(): int;
}
