<?php

declare(strict_types=1);

namespace App\Support\Smoke\ApiContract;

final readonly class ApiContractSmokeContext
{
    /**
     * Create API contract smoke runtime context.
     */
    public function __construct(
        public string $adminToken,
    ) {}
}
