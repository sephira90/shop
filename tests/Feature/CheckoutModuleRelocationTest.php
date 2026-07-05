<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Checkout\Contracts\CheckoutServiceInterface;
use App\Domains\Checkout\Contracts\CheckoutShippingCostResolver;
use App\Domains\Checkout\Middleware\EnsureIdempotencyKeyMiddleware;
use App\Domains\Checkout\Services\CheckoutService;
use App\Domains\Checkout\Services\FreeCheckoutShippingCostResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * C4 relocation smoke: verifies the Checkout slice continues to resolve and
 * respond identically after the namespace move into app/Domains/Checkout/.
 *
 * The detailed wire-contract verification stays in the existing checkout
 * feature tests (CartCheckoutTest, GuestCheckoutTest, etc.); this test
 * specifically locks the module wiring (provider bindings, contract surfaces,
 * controller namespace, middleware alias FQCN, rate limiter registration) so
 * a regression in the relocation is caught with a focused failure message.
 */
class CheckoutModuleRelocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_module_contracts_are_bound_to_module_implementations(): void
    {
        $this->assertInstanceOf(
            CheckoutService::class,
            $this->app->make(CheckoutServiceInterface::class),
            'CheckoutServiceInterface contract must resolve to the module implementation.',
        );

        $this->assertInstanceOf(
            FreeCheckoutShippingCostResolver::class,
            $this->app->make(CheckoutShippingCostResolver::class),
            'CheckoutShippingCostResolver contract must resolve to the module implementation.',
        );
    }

    public function test_checkout_controller_resolves_from_module_namespace(): void
    {
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        $placeRoute = $routes->getByAction('App\\Domains\\Checkout\\Controllers\\CheckoutController@placeOrder');
        $payRoute = $routes->getByAction('App\\Domains\\Checkout\\Controllers\\CheckoutController@pay');

        $this->assertNotNull(
            $placeRoute,
            'CheckoutController::placeOrder must be registered under the App\\Domains\\Checkout\\Controllers namespace.',
        );

        $this->assertNotNull(
            $payRoute,
            'CheckoutController::pay must be registered under the App\\Domains\\Checkout\\Controllers namespace.',
        );
    }

    public function test_idempotency_key_middleware_alias_resolves_to_module_namespace(): void
    {
        $resolvedMiddleware = app('router')->getMiddleware();

        $this->assertArrayHasKey(
            'idempotency.key',
            $resolvedMiddleware,
            'idempotency.key middleware alias must be registered.',
        );

        $this->assertSame(
            EnsureIdempotencyKeyMiddleware::class,
            $resolvedMiddleware['idempotency.key'],
            'idempotency.key middleware alias must resolve to the module middleware class.',
        );
    }

    public function test_checkout_rate_limiter_is_registered(): void
    {
        $this->assertTrue(
            RateLimiter::limiter('checkout') !== null,
            'throttle:checkout rate limiter must be registered (relocated from AppServiceProvider to CheckoutServiceProvider).',
        );
    }
}
