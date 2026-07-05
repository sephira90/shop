<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Cart\Contracts\CartMutationServiceInterface;
use App\Domains\Cart\Contracts\CartServiceInterface;
use App\Domains\Cart\Services\CartMutationService;
use App\Domains\Cart\Services\CartService;
use App\Domains\Checkout\Contracts\CheckoutServiceInterface;
use App\Domains\Checkout\Services\CheckoutService;
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
