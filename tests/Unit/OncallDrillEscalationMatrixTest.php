<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Oncall\OncallDrillEscalationMatrix;
use Tests\TestCase;

class OncallDrillEscalationMatrixTest extends TestCase
{
    public function test_for_check_returns_stable_mapping_for_known_check(): void
    {
        $mapping = (new OncallDrillEscalationMatrix)->forCheck('oncall_webhook_flow_smoke');

        $this->assertSame([
            'severity' => 'SEV-2',
            'owner' => 'fulfillment-oncall',
            'next_step' => 'Investigate payment/shipping webhook chain and idempotency flow.',
        ], $mapping);
    }

    public function test_for_check_returns_default_mapping_for_unknown_check(): void
    {
        $mapping = (new OncallDrillEscalationMatrix)->forCheck('unknown-check');

        $this->assertSame([
            'severity' => 'SEV-3',
            'owner' => 'oncall',
            'next_step' => 'Review command output and run targeted diagnostics.',
        ], $mapping);
    }
}
