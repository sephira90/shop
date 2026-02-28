<?php

declare(strict_types=1);

namespace App\Support\Oncall\Dto;

final readonly class OncallDrillRunResultDto
{
    /**
     * @param  list<OncallDrillCheckResultDto>  $results
     * @param  list<OncallDrillFailureDto>  $failures
     */
    public function __construct(
        public array $results,
        public array $failures,
    ) {}

    public function passed(): bool
    {
        return $this->failures === [];
    }
}
