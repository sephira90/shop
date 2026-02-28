<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Smoke\Performance\Dto\PerformanceSmokeMeasurementDto;
use App\Support\Smoke\Performance\Dto\PerformanceSmokeRunResultDto;
use App\Support\Smoke\Performance\PerformanceSmokeOutputBuilder;
use App\Support\Smoke\SmokeCommandOutputFactory;
use App\Support\Smoke\SmokeRollbackPolicy;
use Tests\TestCase;

class PerformanceSmokeOutputBuilderTest extends TestCase
{
    public function test_build_formats_table_rows_and_warning_message(): void
    {
        $builder = new PerformanceSmokeOutputBuilder(
            new SmokeCommandOutputFactory(new SmokeRollbackPolicy),
        );

        $output = $builder->build(new PerformanceSmokeRunResultDto(
            measurements: [
                new PerformanceSmokeMeasurementDto('cart_show', 12.345, 3),
            ],
            violations: [],
            rolledBack: true,
        ));

        $this->assertSame(['check', 'duration_ms', 'queries'], $output->headers);
        $this->assertSame([['cart_show', '12.35', '3']], $output->rows);
        $this->assertSame(
            'Production safeguard: smoke data rolled back. Use --persist to keep records.',
            $output->warningMessage,
        );
        $this->assertSame('Performance smoke checks passed.', $output->successMessage);
    }
}
