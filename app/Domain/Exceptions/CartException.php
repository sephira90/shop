<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use DomainException;

final class CartException extends DomainException
{
    public static function authenticatedUserNotFound(): self
    {
        return new self('Authenticated user no longer exists.');
    }

    public static function cartOwnershipMismatch(): self
    {
        return new self('Cart ownership mismatch.');
    }

    public static function cartNotFound(): self
    {
        return new self('Cart not found.');
    }

    public static function variantNotAvailable(): self
    {
        return new self('Selected variant is not available.');
    }

    public static function insufficientStockForVariant(): self
    {
        return new self('Insufficient stock for selected variant.');
    }

    public static function guestTokenRequired(): self
    {
        return new self('Guest token is required.');
    }
}
