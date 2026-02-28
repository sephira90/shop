<?php

declare(strict_types=1);

namespace App\Support\Oncall;

use App\Support\Oncall\Dto\OncallDrillCheckResultDto;
use App\Support\Oncall\Dto\OncallDrillFailureDto;
use App\Support\Oncall\Dto\OncallDrillRunResultDto;
use App\Support\Operations\ConsoleCommandRunner;
use Illuminate\Support\Str;

final class OncallDrillRunner
{
    public function __construct(
        private readonly OncallDrillCheckPlanFactory $checkPlanFactory,
        private readonly OncallDrillEscalationMatrix $escalationMatrix,
        private readonly ConsoleCommandRunner $commandRunner,
    ) {}

    public function run(bool $withWriteSmokes, bool $persist): OncallDrillRunResultDto
    {
        $results = [];
        $failures = [];

        foreach ($this->checkPlanFactory->build(
            withWriteSmokes: $withWriteSmokes,
            persist: $persist,
        ) as $check) {
            $commandResult = $this->commandRunner->run($check->command, $check->parameters);
            $status = $commandResult->succeeded() ? 'ok' : 'fail';

            $results[] = new OncallDrillCheckResultDto(
                check: $check->name,
                command: $check->command,
                status: $status,
                exitCode: $commandResult->exitCode,
            );

            if ($commandResult->succeeded()) {
                continue;
            }

            $escalation = $this->escalationMatrix->forCheck($check->name);

            $failures[] = new OncallDrillFailureDto(
                check: $check->name,
                severity: $escalation['severity'],
                owner: $escalation['owner'],
                nextStep: $escalation['next_step'],
                outputExcerpt: Str::limit($commandResult->output, 320),
            );
        }

        return new OncallDrillRunResultDto($results, $failures);
    }
}
