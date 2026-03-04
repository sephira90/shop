<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Checkout\Dto\CheckoutAddressInputDto;
use App\Application\Checkout\Dto\CheckoutPlaceOrderInputDto;
use App\Domain\ValueObjects\Money;
use App\Models\Cart;
use App\Services\Checkout\CheckoutShippingCostResolver;
use App\Services\Checkout\FreeCheckoutShippingCostResolver;
use Tests\TestCase;

class CheckoutShippingCostResolverBindingTest extends TestCase
{
    public function test_checkout_shipping_cost_resolver_is_bound_to_free_implementation(): void
    {
        $resolver = app(CheckoutShippingCostResolver::class);

        $this->assertInstanceOf(FreeCheckoutShippingCostResolver::class, $resolver);
    }

    public function test_free_shipping_cost_resolver_returns_zero(): void
    {
        $resolver = app(CheckoutShippingCostResolver::class);
        $cart = new Cart;
        $cart->id = 'cart-1';

        $checkoutInput = new CheckoutPlaceOrderInputDto(
            guestToken: null,
            email: 'guest@example.com',
            currency: 'USD',
            couponCode: null,
            billingAddress: new CheckoutAddressInputDto(
                line1: '1 Main Street',
                city: 'Moscow',
                country: 'RU',
                postcode: '101000',
            ),
            shippingAddress: new CheckoutAddressInputDto(
                line1: '1 Main Street',
                city: 'Moscow',
                country: 'RU',
                postcode: '101000',
            ),
        );

        $shippingCost = $resolver->resolve($cart, $checkoutInput);

        $this->assertInstanceOf(Money::class, $shippingCost);
        $this->assertSame(0, $shippingCost->amountCents());
        $this->assertSame('USD', $shippingCost->currency());
    }
}
