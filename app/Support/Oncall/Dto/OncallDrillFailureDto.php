<?php

declare(strict_types=1);

namespace App\Support\Oncall\Dto;

final readonly class OncallDrillFailureDto
{
    public function __construct(
        public string $check,
        public string $severity,
        public string $owner,
        public string $nextStep,
        public string $outputExcerpt,
    ) {}
}
