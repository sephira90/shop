<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Smoke\SmokeRollbackPolicy;
use Tests\TestCase;

class SmokeRollbackPolicyTest extends TestCase
{
    public function test_should_rollback_only_for_production_without_persist(): void
    {
        $policy = new SmokeRollbackPolicy;

        config()->set('app.env', 'production');
        $this->assertTrue($policy->shouldRollback(false));
        $this->assertFalse($policy->shouldRollback(true));

        config()->set('app.env', 'local');
        $this->assertFalse($policy->shouldRollback(false));
    }

    public function test_warning_message_is_emitted_only_for_rolled_back_run(): void
    {
        $policy = new SmokeRollbackPolicy;

        $this->assertSame(
            'Production safeguard: smoke data rolled back. Use --persist to keep records.',
            $policy->warningMessage(true),
        );
        $this->assertNull($policy->warningMessage(false));
    }
}
