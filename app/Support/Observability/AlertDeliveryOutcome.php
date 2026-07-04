<?php

declare(strict_types=1);

namespace App\Support\Observability;

/**
 * Outcome taxonomy for a single observability alert channel delivery.
 *
 * Replaces the previous boolean contract so the router can distinguish a
 * disabled channel (intentional configuration, not a failure) from a
 * delivery that was attempted and failed. Only attempted deliveries count
 * toward the aggregate all-attempted-channels-failed signal.
 */
enum AlertDeliveryOutcome: string
{
    /** The channel is disabled by configuration; no delivery was attempted. */
    case DISABLED = 'disabled';

    /** The channel accepted and delivered the alert. */
    case DELIVERED = 'delivered';

    /** The channel attempted delivery and failed (config, request, or remote error). */
    case FAILED = 'failed';

    public function isDisabled(): bool
    {
        return $this === self::DISABLED;
    }

    public function isDelivered(): bool
    {
        return $this === self::DELIVERED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * Whether the channel actually attempted delivery. Disabled channels
     * never count as an attempt; delivered and failed channels do.
     */
    public function wasAttempted(): bool
    {
        return $this === self::DELIVERED || $this === self::FAILED;
    }
}
