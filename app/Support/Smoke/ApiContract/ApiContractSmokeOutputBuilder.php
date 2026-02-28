<?php

declare(strict_types=1);

namespace App\Support\Smoke\ApiContract;

use App\Support\Smoke\ApiContract\Dto\ApiContractSmokeRunResultDto;
use App\Support\Smoke\Dto\SmokeCommandOutputDto;
use App\Support\Smoke\SmokeCheckResult;
use App\Support\Smoke\SmokeCommandOutputFactory;

final class ApiContractSmokeOutputBuilder
{
    public function __construct(
        private readonly SmokeCommandOutputFactory $outputFactory,
    ) {}

    public function build(ApiContractSmokeRunResultDto $result): SmokeCommandOutputDto
    {
        return $this->outputFactory->build(
            headers: ['check', 'status', 'result'],
            rows: array_map(
                static fn (SmokeCheckResult $check): array => $check->toTableRow(),
                $result->checks,
            ),
            successMessage: 'API contract smoke checks passed.',
            rolledBack: $result->rolledBack,
        );
    }
}
