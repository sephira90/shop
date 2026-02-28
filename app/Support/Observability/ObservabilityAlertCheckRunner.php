<?php

declare(strict_types=1);

namespace App\Support\Observability;

use App\Support\Observability\Dto\ObservabilityAlertCheckRunResultDto;
use App\Support\Observability\Dto\ObservabilityAlertPayloadDto;
use App\Support\Operations\ConsoleCommandRunner;

final class ObservabilityAlertCheckRunner
{
    public function __construct(
        private readonly ObservabilityReportCommandInvocationFactory $reportCommandFactory,
        private readonly ObservabilityAlertRouter $alertRouter,
        private readonly ConsoleCommandRunner $commandRunner,
    ) {}

    public function run(): ObservabilityAlertCheckRunResultDto
    {
        $reportCommand = $this->reportCommandFactory->makeFromAlertConfig();
        $reportResult = $this->commandRunner->run($reportCommand->command, $reportCommand->parameters);

        if ($reportResult->succeeded()) {
            return new ObservabilityAlertCheckRunResultDto(
                reportResult: $reportResult,
                routingResult: null,
            );
        }

        $routingResult = $this->alertRouter->routeFailureAlert(new ObservabilityAlertPayloadDto(
            command: $reportCommand->command,
            exitCode: $reportResult->exitCode,
            output: $reportResult->output,
            parameters: $reportCommand->stringifyParameters(),
            happenedAt: now()->toIso8601String(),
        ));

        return new ObservabilityAlertCheckRunResultDto(
            reportResult: $reportResult,
            routingResult: $routingResult,
        );
    }
}
