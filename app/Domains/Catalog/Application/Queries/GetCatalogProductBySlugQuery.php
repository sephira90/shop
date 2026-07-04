<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Application\Queries;

final readonly class GetCatalogProductBySlugQuery
{
    /**
     * Create query payload for catalog product show.
     */
    public function __construct(
        public string $slug,
    ) {}
}
