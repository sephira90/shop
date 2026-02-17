<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\ShippingGatewayInterface;
use App\Infrastructure\Payments\FakePaymentGateway;
use App\Infrastructure\Shipping\FakeShippingGateway;
use App\Models\Order;
use App\Models\Product;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, FakePaymentGateway::class);
        $this->app->bind(ShippingGatewayInterface::class, FakeShippingGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);

        RateLimiter::for('checkout', static fn (Request $request): Limit => Limit::perMinute(6)
            ->by((string) ($request->user()?->id ?? $request->ip())));

        RateLimiter::for('webhook', static fn (Request $request): Limit => Limit::perMinute(120)
            ->by($request->ip()));

        RateLimiter::for('search', static fn (Request $request): Limit => Limit::perMinute(90)
            ->by((string) ($request->user()?->id ?? $request->ip())));
    }
}
