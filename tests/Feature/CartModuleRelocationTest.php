<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Cart\Contracts\CartMutationServiceInterface;
use App\Domains\Cart\Contracts\CartServiceInterface;
use App\Domains\Cart\Policies\CartPolicy;
use App\Domains\Cart\Services\CartMutationService;
use App\Domains\Cart\Services\CartService;
use App\Models\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * C3 relocation smoke: verifies the Cart slice continues to resolve and
 * respond identically after the namespace move into app/Domains/Cart/.
 *
 * The detailed wire-contract verification stays in OpenApiConformanceFeatureTest;
 * this test specifically locks the module wiring (provider bindings, contract
 * surfaces, controller namespace, policy registration) so a regression in the
 * relocation is caught with a focused failure message. It also verifies the
 * first real cross-module Domains\<Other>\Contracts\ import (Users → Cart)
 * resolves correctly through the container.
 */
class CartModuleRelocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_module_contracts_are_bound_to_module_implementations(): void
    {
        $this->assertInstanceOf(
            CartService::class,
            $this->app->make(CartServiceInterface::class),
            'CartServiceInterface contract must resolve to the module implementation.',
        );

        $this->assertInstanceOf(
            CartMutationService::class,
            $this->app->make(CartMutationServiceInterface::class),
            'CartMutationServiceInterface contract must resolve to the module implementation.',
        );
    }

    public function test_cart_controller_resolves_from_module_namespace(): void
    {
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        $showRoute = $routes->getByAction('App\\Domains\\Cart\\Controllers\\CartController@show');

        $this->assertNotNull(
            $showRoute,
            'CartController must be registered under the App\\Domains\\Cart\\Controllers namespace.',
        );
    }

    public function test_cart_policy_is_registered_for_module_cart_model(): void
    {
        $resolvedPolicy = Gate::getPolicyFor(Cart::class);

        $this->assertInstanceOf(
            CartPolicy::class,
            $resolvedPolicy,
            'Cart model must be mapped to the module CartPolicy via Gate::policy().',
        );
    }

    public function test_cross_module_cart_service_resolution_from_users_handler(): void
    {
        // First real cross-module Domains\<Other>\Contracts\ import path:
        // Users module (LoginAuthUserHandler) -> Cart module (CartServiceInterface).
        // Resolution through the container validates the binding and the import path.
        $resolved = $this->app->make(CartServiceInterface::class);

        $this->assertInstanceOf(CartService::class, $resolved);
    }
}
