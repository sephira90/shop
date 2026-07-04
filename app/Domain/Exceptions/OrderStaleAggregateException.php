<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use DomainException;

/**
 * Thrown when an Orders aggregate (typically Order) became stale before a
 * state-changing operation could acquire it under a row lock.
 *
 * This is a concurrency/conflict signal, not a validation failure: the
 * caller's intent was valid against the previously-known state, but the
 * aggregate is no longer present (deleted by a concurrent transaction) or
 * could not be locked for the operation. The API renderer maps it to HTTP
 * 409 Conflict with the `stale_aggregate` error code. In queue/job contexts
 * it propagates to the worker and fails the job without producing an HTTP
 * envelope.
 */
final class OrderStaleAggregateException extends DomainException
{
    public static function forPaymentInitiation(): self
    {
        return new self('Order not found.');
    }

    public static function forShipmentDispatch(): self
    {
        return new self('Order not found for shipment dispatch.');
    }
}
