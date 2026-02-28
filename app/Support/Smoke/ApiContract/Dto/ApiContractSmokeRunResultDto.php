<?php

declare(strict_types=1);

namespace App\Support\Smoke\ApiContract\Dto;

use App\Support\Smoke\SmokeCheckResult;

final readonly class ApiContractSmokeRunResultDto
{
    /**
     * @param  list<SmokeCheckResult>  $checks
     */
    public function __construct(
        public array $checks,
        public bool $rolledBack,
    ) {}
}
