<?php

declare(strict_types=1);

namespace App\Support\Smoke;

use App\Support\Smoke\Dto\SmokeCommandOutputDto;

final class SmokeCommandOutputFactory
{
    public function __construct(
        private readonly SmokeRollbackPolicy $rollbackPolicy,
    ) {}

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    public function build(array $headers, array $rows, string $successMessage, bool $rolledBack): SmokeCommandOutputDto
    {
        return new SmokeCommandOutputDto(
            headers: $headers,
            rows: $rows,
            warningMessage: $this->rollbackPolicy->warningMessage($rolledBack),
            successMessage: $successMessage,
        );
    }
}
