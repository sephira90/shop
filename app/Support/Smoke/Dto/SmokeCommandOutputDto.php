<?php

declare(strict_types=1);

namespace App\Support\Smoke\Dto;

final readonly class SmokeCommandOutputDto
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    public function __construct(
        public array $headers,
        public array $rows,
        public ?string $warningMessage,
        public string $successMessage,
    ) {}
}
