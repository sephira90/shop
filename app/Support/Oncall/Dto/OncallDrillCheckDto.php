<?php

declare(strict_types=1);

namespace App\Support\Oncall\Dto;

final readonly class OncallDrillCheckDto
{
    /**
     * @param  array<string,bool|float|int|string>  $parameters
     */
    public function __construct(
        public string $name,
        public string $command,
        public array $parameters,
    ) {}
}
