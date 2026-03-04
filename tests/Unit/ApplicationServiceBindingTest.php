<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\CartMutationServiceInterface;
use App\Contracts\CartServiceInterface;
use App\Contracts\CheckoutServiceInterface;
use App\Services\Cart\CartMutationService;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use Tests\TestCase;

final class ApplicationServiceBindingTest extends TestCase
{
    public function test_cart_mutation_service_contract_is_bound_to_default_implementation(): void
    {
        $this->assertInstanceOf(CartMutationService::class, $this->app->make(CartMutationServiceInterface::class));
    }

    public function test_cart_service_contract_is_bound_to_default_implementation(): void
    {
        $this->assertInstanceOf(CartService::class, $this->app->make(CartServiceInterface::class));
    }

    public function test_checkout_service_contract_is_bound_to_default_implementation(): void
    {
        $this->assertInstanceOf(CheckoutService::class, $this->app->make(CheckoutServiceInterface::class));
    }
}
