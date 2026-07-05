<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Checkout;

use App\Domain\Exceptions\CheckoutException;
use App\Domains\Checkout\Application\Dto\CheckoutAddressInputDto;
use App\Domains\Checkout\Application\Dto\CheckoutPlaceOrderInputDto;
use App\Domains\Checkout\Services\CheckoutRequestIdentityResolver;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutRequestIdentityResolverTest extends TestCase
{
    public function test_resolve_uses_user_scope_and_stable_request_hash(): void
    {
        $cart = new Cart;
        $cart->id = Str::uuid()->toString();
        $cart->guest_token = 'guest-token-ignored';

        $user = new User;
        $user->id = 42;

        $input = $this->makeCheckoutInput();

        $identity = app(CheckoutRequestIdentityResolver::class)->resolve($cart, $input, $user);

        $this->assertSame('user:42', $identity->scopeKey);
        $this->assertSame(
            hash('sha256', json_encode([$cart->id, $input->toHashPayload()], JSON_THROW_ON_ERROR)),
            $identity->requestHash,
        );
    }

    public function test_resolve_uses_guest_token_scope_for_guest_checkout(): void
    {
        $cart = new Cart;
        $cart->id = Str::uuid()->toString();
        $cart->guest_token = 'guest-scope-token';

        $identity = app(CheckoutRequestIdentityResolver::class)->resolve($cart, $this->makeCheckoutInput(), null);

        $this->assertSame('guest:guest-scope-token', $identity->scopeKey);
    }

    public function test_resolve_rejects_guest_checkout_without_guest_token(): void
    {
        $cart = new Cart;
        $cart->id = Str::uuid()->toString();
        $cart->guest_token = null;

        $this->expectException(CheckoutException::class);
        $this->expectExceptionMessage('Guest checkout requires guest token.');

        app(CheckoutRequestIdentityResolver::class)->resolve($cart, $this->makeCheckoutInput(), null);
    }

    private function makeCheckoutInput(): CheckoutPlaceOrderInputDto
    {
        return new CheckoutPlaceOrderInputDto(
            guestToken: 'guest-token',
            email: 'guest@example.com',
            currency: 'USD',
            couponCode: 'SAVE10',
            billingAddress: new CheckoutAddressInputDto(
                line1: '1 Main Street',
                city: 'New York',
                country: 'US',
                postcode: '10001',
            ),
            shippingAddress: new CheckoutAddressInputDto(
                line1: '1 Main Street',
                city: 'New York',
                country: 'US',
                postcode: '10001',
            ),
        );
    }
}
