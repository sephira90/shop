<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Services;

use App\Domain\Exceptions\CheckoutException;
use App\Domains\Checkout\Application\Dto\CheckoutPlaceOrderInputDto;
use App\Domains\Checkout\Services\Dto\CheckoutRequestIdentityDto;
use App\Models\Cart;
use App\Models\User;

final class CheckoutRequestIdentityResolver
{
    public function resolve(Cart $cart, CheckoutPlaceOrderInputDto $checkoutInput, ?User $user): CheckoutRequestIdentityDto
    {
        return new CheckoutRequestIdentityDto(
            scopeKey: $this->resolveScopeKey($cart, $user),
            requestHash: hash('sha256', json_encode([$cart->id, $checkoutInput->toHashPayload()], JSON_THROW_ON_ERROR)),
        );
    }

    private function resolveScopeKey(Cart $cart, ?User $user): string
    {
        if ($user !== null) {
            return 'user:'.$user->id;
        }

        if (! empty($cart->guest_token)) {
            return 'guest:'.$cart->guest_token;
        }

        throw CheckoutException::guestTokenRequired();
    }
}
