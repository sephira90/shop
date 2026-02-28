<?php

declare(strict_types=1);

namespace App\Support\Smoke\WebhookFlow\Dto;

final readonly class WebhookFlowSmokeRunResultDto
{
    public function __construct(
        public WebhookFlowSmokeResultDto $result,
        public bool $rolledBack,
    ) {}
}
