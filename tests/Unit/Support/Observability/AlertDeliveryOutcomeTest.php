<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Observability;

use App\Support\Observability\AlertDeliveryOutcome;
use Tests\TestCase;

/**
 * Verifies the observability alert delivery outcome taxonomy used by the
 * channel/router contract: an explicit contract distinguishes disabled,
 * delivered, and failed deliveries so the router can emit an aggregate
 * failure signal only when at least one enabled channel attempted delivery
 * and every attempt failed.
 */
class AlertDeliveryOutcomeTest extends TestCase
{
    public function test_enum_exposes_three_outcomes_with_stable_string_values(): void
    {
        $this->assertSame('disabled', AlertDeliveryOutcome::DISABLED->value);
        $this->assertSame('delivered', AlertDeliveryOutcome::DELIVERED->value);
        $this->assertSame('failed', AlertDeliveryOutcome::FAILED->value);
    }

    public function test_outcome_predicates_disambiguate_disabled_from_failed(): void
    {
        $this->assertTrue(AlertDeliveryOutcome::DISABLED->isDisabled());
        $this->assertFalse(AlertDeliveryOutcome::DISABLED->isFailed());
        $this->assertFalse(AlertDeliveryOutcome::DISABLED->isDelivered());

        $this->assertTrue(AlertDeliveryOutcome::FAILED->isFailed());
        $this->assertFalse(AlertDeliveryOutcome::FAILED->isDisabled());
        $this->assertFalse(AlertDeliveryOutcome::FAILED->isDelivered());

        $this->assertTrue(AlertDeliveryOutcome::DELIVERED->isDelivered());
        $this->assertFalse(AlertDeliveryOutcome::DELIVERED->isDisabled());
        $this->assertFalse(AlertDeliveryOutcome::DELIVERED->isFailed());
    }

    public function test_disabled_outcome_is_never_an_attempted_delivery(): void
    {
        $this->assertFalse(AlertDeliveryOutcome::DISABLED->wasAttempted());
        $this->assertTrue(AlertDeliveryOutcome::DELIVERED->wasAttempted());
        $this->assertTrue(AlertDeliveryOutcome::FAILED->wasAttempted());
    }
}
