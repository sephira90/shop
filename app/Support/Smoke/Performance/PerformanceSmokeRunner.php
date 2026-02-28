<?php

declare(strict_types=1);

namespace App\Support\Smoke\Performance;

use App\Support\Smoke\Performance\Contracts\PerformanceSmokeScenario;
use App\Support\Smoke\Performance\Dto\PerformanceSmokeMeasurementDto;
use App\Support\Smoke\Performance\Dto\PerformanceSmokeOptionsDto;
use App\Support\Smoke\Performance\Dto\PerformanceSmokeRunResultDto;
use App\Support\Smoke\SmokePersistenceGuard;
use App\Support\Smoke\SmokeRollbackPolicy;

final class PerformanceSmokeRunner
{
    public function __construct(
        private readonly PerformanceSmokeSetupFactory $setupFactory,
        private readonly PerformanceSmokeScenarioRegistry $scenarioRegistry,
        private readonly PerformanceSmokeProfiler $profiler,
        private readonly SmokePersistenceGuard $persistenceGuard,
        private readonly SmokeRollbackPolicy $rollbackPolicy,
    ) {}

    public function run(PerformanceSmokeOptionsDto $options): PerformanceSmokeRunResultDto
    {
        $observabilityEnabled = (bool) config('observability.enabled', true);
        config()->set('observability.enabled', false);

        try {
            $execution = $this->persistenceGuard->run(
                $this->rollbackPolicy->shouldRollback($options->execution->persist),
                fn (): array => $this->runChecks($options),
            );
        } finally {
            config()->set('observability.enabled', $observabilityEnabled);
        }

        /** @var array{measurements:list<PerformanceSmokeMeasurementDto>,violations:list<string>} $result */
        $result = $execution['result'];

        return new PerformanceSmokeRunResultDto(
            measurements: $result['measurements'],
            violations: $result['violations'],
            rolledBack: $execution['rolled_back'],
        );
    }

    /**
     * @return array{measurements:list<PerformanceSmokeMeasurementDto>,violations:list<string>}
     */
    private function runChecks(PerformanceSmokeOptionsDto $options): array
    {
        $scenarios = $this->scenarioRegistry->scenarios($options->execution->onlyScenarios);
        $scenarioNames = array_map(
            static fn (PerformanceSmokeScenario $scenario): string => $scenario->name(),
            $scenarios,
        );
        $context = $this->setupFactory->build($scenarioNames);
        $measurements = [];
        $violations = [];

        foreach ($scenarios as $scenario) {
            $measurement = $this->profiler->measure(
                $scenario->name(),
                function () use ($scenario, $context): void {
                    $scenario->run($context);
                },
                $scenario->usesRollback(),
            );

            $measurements[] = $measurement;
            $budget = $options->budgetFor($measurement->name);
            $this->collectViolations($measurement, $budget->maxMs, $budget->maxQueries, $violations);
        }

        return [
            'measurements' => $measurements,
            'violations' => $violations,
        ];
    }

    /**
     * @param  list<string>  $violations
     */
    private function collectViolations(PerformanceSmokeMeasurementDto $measurement, float $maxMs, int $maxQueries, array &$violations): void
    {
        if ($measurement->durationMs > $maxMs) {
            $violations[] = sprintf(
                '%s latency budget exceeded: %.2fms > %.2fms',
                $measurement->name,
                $measurement->durationMs,
                $maxMs,
            );
        }

        if ($measurement->queries > $maxQueries) {
            $violations[] = sprintf(
                '%s query budget exceeded: %d > %d',
                $measurement->name,
                $measurement->queries,
                $maxQueries,
            );
        }
    }
}
