<?php

declare(strict_types=1);

namespace App\Support\Oncall;

use App\Support\Observability\ObservabilityReportCommandInvocationFactory;
use App\Support\Oncall\Dto\OncallDrillCheckDto;

final class OncallDrillCheckPlanFactory
{
    public function __construct(
        private readonly ObservabilityReportCommandInvocationFactory $reportCommandFactory,
    ) {}

    /**
     * @return list<OncallDrillCheckDto>
     */
    public function build(bool $withWriteSmokes, bool $persist): array
    {
        $observabilityReport = $this->reportCommandFactory->makeFromAlertConfig();

        $checks = [
            new OncallDrillCheckDto(
                name: 'oncall_healthcheck',
                command: 'app:healthcheck',
                parameters: [],
            ),
            new OncallDrillCheckDto(
                name: 'oncall_observability_slo_report',
                command: $observabilityReport->command,
                parameters: $observabilityReport->parameters,
            ),
            new OncallDrillCheckDto(
                name: 'oncall_cleanup_dry_run',
                command: 'app:maintenance-cleanup',
                parameters: [
                    '--dry-run' => true,
                ],
            ),
        ];

        if (! $withWriteSmokes) {
            return $checks;
        }

        $checks[] = new OncallDrillCheckDto(
            name: 'oncall_api_contract_smoke',
            command: 'app:api-contract-smoke',
            parameters: $persist ? ['--persist' => true] : [],
        );
        $checks[] = new OncallDrillCheckDto(
            name: 'oncall_webhook_flow_smoke',
            command: 'app:webhook-flow-smoke',
            parameters: $persist ? ['--persist' => true] : [],
        );

        return $checks;
    }
}
