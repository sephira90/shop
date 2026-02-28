<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Smoke\SmokeCommandOutputFactory;
use App\Support\Smoke\SmokeRollbackPolicy;
use Tests\TestCase;

class SmokeCommandOutputFactoryTest extends TestCase
{
    public function test_build_returns_normalized_table_output_contract(): void
    {
        $factory = new SmokeCommandOutputFactory(new SmokeRollbackPolicy);

        $output = $factory->build(
            headers: ['check', 'status'],
            rows: [['catalog', 'ok']],
            successMessage: 'Smoke checks passed.',
            rolledBack: true,
        );

        $this->assertSame(['check', 'status'], $output->headers);
        $this->assertSame([['catalog', 'ok']], $output->rows);
        $this->assertSame('Smoke checks passed.', $output->successMessage);
        $this->assertSame(
            'Production safeguard: smoke data rolled back. Use --persist to keep records.',
            $output->warningMessage,
        );
    }
}
