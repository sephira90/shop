<?php

declare(strict_types=1);

namespace App\Support\Smoke\Performance\Dto;

use App\Support\Smoke\Dto\SmokeExecutionOptionsDto;
use DomainException;

final readonly class PerformanceSmokeOptionsDto
{
    /**
     * @param  array<string,PerformanceSmokeBudgetDto>  $budgets
     */
    public function __construct(
        public SmokeExecutionOptionsDto $execution,
        public array $budgets,
    ) {}

    public function budgetFor(string $scenario): PerformanceSmokeBudgetDto
    {
        if (! array_key_exists($scenario, $this->budgets)) {
            throw new DomainException(sprintf('Missing performance smoke budget for scenario "%s".', $scenario));
        }

        return $this->budgets[$scenario];
    }
}
