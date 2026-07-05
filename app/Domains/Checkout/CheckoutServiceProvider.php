<?php

declare(strict_types=1);

namespace App\Domains\Checkout;

use App\Domains\Checkout\Contracts\CheckoutServiceInterface;
use App\Domains\Checkout\Contracts\CheckoutShippingCostResolver;
use App\Domains\Checkout\Services\CheckoutService;
use App\Domains\Checkout\Services\FreeCheckoutShippingCostResolver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class CheckoutServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CheckoutShippingCostResolver::class, FreeCheckoutShippingCostResolver::class);
        $this->app->bind(CheckoutServiceInterface::class, CheckoutService::class);
    }

    public function boot(): void
    {
        RateLimiter::for('checkout', static function (Request $request): Limit {
            $user = $request->user();

            return Limit::perMinute(6)
                ->by($user === null ? $request->ip() : (string) $user->id);
        });
    }
}
