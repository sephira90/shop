<?php

declare(strict_types=1);

namespace App\Support\Smoke\WebhookFlow;

use App\Support\Smoke\SmokePersistenceGuard;
use App\Support\Smoke\SmokeRollbackPolicy;
use App\Support\Smoke\WebhookFlow\Dto\WebhookFlowSmokeResultDto;
use App\Support\Smoke\WebhookFlow\Dto\WebhookFlowSmokeRunResultDto;
use Illuminate\Support\Facades\Config;

final class WebhookFlowSmokeRunner
{
    public function __construct(
        private readonly WebhookFlowScenario $scenario,
        private readonly SmokePersistenceGuard $persistenceGuard,
        private readonly SmokeRollbackPolicy $rollbackPolicy,
    ) {}

    public function run(bool $persist): WebhookFlowSmokeRunResultDto
    {
        $originalQueueConnection = Config::get('queue.default');
        Config::set('queue.default', 'sync');

        try {
            $execution = $this->persistenceGuard->run(
                $this->rollbackPolicy->shouldRollback($persist),
                fn (): WebhookFlowSmokeResultDto => $this->scenario->run(),
            );
        } finally {
            Config::set('queue.default', $originalQueueConnection);
        }

        /** @var WebhookFlowSmokeResultDto $result */
        $result = $execution['result'];

        return new WebhookFlowSmokeRunResultDto(
            result: $result,
            rolledBack: $execution['rolled_back'],
        );
    }
}
