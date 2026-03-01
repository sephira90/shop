<?php

declare(strict_types=1);

namespace App\Support\Smoke;

use App\Support\Data\TypedValue;
use App\Support\Smoke\Dto\SmokeExecutionOptionsDto;

final class SmokeExecutionOptionsResolver
{
    /**
     * @param  array{persist:mixed,only:mixed}  $options
     */
    public function resolve(array $options): SmokeExecutionOptionsDto
    {
        $only = TypedValue::nullableTrimmedString($options['only']) ?? '';

        return new SmokeExecutionOptionsDto(
            persist: (bool) $options['persist'],
            onlyScenarios: $only === ''
                ? []
                : array_values(array_unique(array_filter(array_map(
                    static fn (string $value): string => trim($value),
                    explode(',', $only),
                )))),
        );
    }
}
