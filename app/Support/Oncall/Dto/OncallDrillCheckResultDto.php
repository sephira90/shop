<?php

declare(strict_types=1);

namespace App\Support\Oncall\Dto;

final readonly class OncallDrillCheckResultDto
{
    public function __construct(
        public string $check,
        public string $command,
        public string $status,
        public int $exitCode,
    ) {}
}
