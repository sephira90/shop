<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Events\OrderPlaced;
use App\Models\Order;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Tests\TestCase;

class AfterCommitDispatchSafetyTest extends TestCase
{
    /**
     * Ensure order placed event is deferred until transaction commit.
     */
    public function test_order_placed_event_dispatches_after_commit(): void
    {
        $event = new OrderPlaced(new Order);

        $this->assertInstanceOf(ShouldDispatchAfterCommit::class, $event);
    }
}
