<?php

declare(strict_types=1);

namespace App\Support\Smoke\ApiContract;

use App\Support\Smoke\Api\ApiSmokeAssertions;
use App\Support\Smoke\Api\ApiSmokeHttpClient;
use App\Support\Smoke\SmokeCheckResult;

interface ApiContractScenario
{
    /**
     * Execute API contract checks for one domain scenario.
     *
     * @return list<SmokeCheckResult>
     */
    public function run(ApiSmokeHttpClient $client, ApiSmokeAssertions $assertions, ApiContractSmokeContext $context): array;
}
