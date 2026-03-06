<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use DomainException;

final class OrderTransitionException extends DomainException
{
    public static function orderNotFoundForStatusUpdate(): self
    {
        return new self('Order not found for status update.');
    }

    public static function paymentStatusTransitionNotAllowed(): self
    {
        return new self('Payment status transition is not allowed.');
    }

    public static function shipmentStatusTransitionNotAllowed(): self
    {
        return new self('Shipment status transition is not allowed.');
    }

    public static function orderStatusTransitionNotAllowed(): self
    {
        return new self('Order status transition is not allowed.');
    }

    public static function inventoryRowNotFoundForRelease(): self
    {
        return new self('Inventory row not found for order cancellation release.');
    }
}
