<?php

declare(strict_types=1);

namespace App\Support\Smoke\Dto;

final readonly class SmokeExecutionOptionsDto
{
    /**
     * @param  list<string>  $onlyScenarios
     */
    public function __construct(
        public bool $persist,
        public array $onlyScenarios,
    ) {}
}
