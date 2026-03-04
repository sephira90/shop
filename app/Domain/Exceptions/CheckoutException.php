<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use DomainException;
use Throwable;

final class CheckoutException extends DomainException
{
    public static function cartNotFound(): self
    {
        return new self('Cart not found.');
    }

    public static function cartIsEmpty(): self
    {
        return new self('Cart is empty.');
    }

    public static function cartNotActiveForCheckout(): self
    {
        return new self('Cart is not active for checkout.');
    }

    public static function cartContainsUnavailableItems(): self
    {
        return new self('Cart contains unavailable items.');
    }

    public static function guestTokenRequired(): self
    {
        return new self('Guest checkout requires guest token.');
    }

    public static function insufficientStockDuringCheckout(): self
    {
        return new self('Insufficient stock during checkout.');
    }

    public static function idempotencyPayloadMismatch(): self
    {
        return new self('Idempotency key reused with different payload.');
    }

    public static function idempotencyCartMismatch(): self
    {
        return new self('Idempotency key reused for a different cart.');
    }

    public static function orderNotFoundAfterFinalization(): self
    {
        return new self('Order not found after checkout finalization.');
    }

    public static function couponCodeInvalid(): self
    {
        return new self('Coupon code is invalid.');
    }

    public static function couponExpired(): self
    {
        return new self('Coupon has expired.');
    }

    public static function couponUsageLimitExceeded(): self
    {
        return new self('Coupon usage limit exceeded.');
    }

    public static function promotionNotAvailable(): self
    {
        return new self('Promotion is not available.');
    }

    public static function promotionNotStartedYet(): self
    {
        return new self('Promotion has not started yet.');
    }

    public static function promotionHasEnded(): self
    {
        return new self('Promotion has ended.');
    }

    public static function promotionUsageLimitExceeded(): self
    {
        return new self('Promotion usage limit exceeded.');
    }

    public static function promotionTypeInvalid(Throwable $previous): self
    {
        return new self('Promotion type is invalid.', 0, $previous);
    }
}
