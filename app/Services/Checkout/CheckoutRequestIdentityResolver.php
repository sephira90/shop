<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Application\Checkout\Dto\CheckoutPlaceOrderInputDto;
use App\Models\Cart;
use App\Models\User;
use App\Services\Checkout\Dto\CheckoutRequestIdentityDto;
use DomainException;

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

        throw new DomainException('Guest checkout requires guest token.');
    }
}
