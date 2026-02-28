<?php

declare(strict_types=1);

namespace App\Support\Smoke\ApiContract;

use App\Support\Smoke\Api\ApiSmokeAssertions;
use App\Support\Smoke\Api\ApiSmokeHttpClient;
use App\Support\Smoke\ApiContract\Dto\ApiContractSmokeRunResultDto;
use App\Support\Smoke\Dto\SmokeExecutionOptionsDto;
use App\Support\Smoke\SmokeCheckResult;
use App\Support\Smoke\SmokePersistenceGuard;
use App\Support\Smoke\SmokeRollbackPolicy;

final class ApiContractSmokeRunner
{
    public function __construct(
        private readonly ApiSmokeHttpClient $apiClient,
        private readonly ApiSmokeAssertions $assertions,
        private readonly SmokePersistenceGuard $persistenceGuard,
        private readonly SmokeRollbackPolicy $rollbackPolicy,
        private readonly ApiContractSmokeContextFactory $contextFactory,
        private readonly ApiContractSmokeScenarioRegistry $scenarioRegistry,
    ) {}

    public function run(SmokeExecutionOptionsDto $options): ApiContractSmokeRunResultDto
    {
        $execution = $this->persistenceGuard->run(
            $this->rollbackPolicy->shouldRollback($options->persist),
            fn (): array => $this->runChecks($options),
        );

        /** @var list<SmokeCheckResult> $checks */
        $checks = $execution['result'];

        return new ApiContractSmokeRunResultDto(
            checks: $checks,
            rolledBack: $execution['rolled_back'],
        );
    }

    /**
     * @return list<SmokeCheckResult>
     */
    private function runChecks(SmokeExecutionOptionsDto $options): array
    {
        $context = $this->contextFactory->build();
        $checks = [];

        foreach ($this->scenarioRegistry->scenarios($options->onlyScenarios) as $scenario) {
            $checks = array_merge($checks, $scenario->run($this->apiClient, $this->assertions, $context));
        }

        return $checks;
    }
}
