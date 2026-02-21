<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\ShippingGatewayInterface;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Policies\CategoryPolicy;
use App\Policies\CouponPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PromotionPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            PaymentGatewayInterface::class,
            fn (): PaymentGatewayInterface => $this->resolveGatewayDriver('payment', PaymentGatewayInterface::class),
        );

        $this->app->bind(
            ShippingGatewayInterface::class,
            fn (): ShippingGatewayInterface => $this->resolveGatewayDriver('shipping', ShippingGatewayInterface::class),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Coupon::class, CouponPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Promotion::class, PromotionPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);

        RateLimiter::for('checkout', static fn (Request $request): Limit => Limit::perMinute(6)
            ->by((string) ($request->user()?->id ?? $request->ip())));

        RateLimiter::for('webhook', static fn (Request $request): Limit => Limit::perMinute(120)
            ->by($request->ip()));

        RateLimiter::for('search', static fn (Request $request): Limit => Limit::perMinute(90)
            ->by((string) ($request->user()?->id ?? $request->ip())));
    }

    /**
     * Resolve configured gateway implementation from `{domain}.driver`.
     *
     * @template TGateway of object
     *
     * @param  'payment'|'shipping'  $domain
     * @param  class-string<TGateway>  $contract
     * @return TGateway
     */
    private function resolveGatewayDriver(string $domain, string $contract): object
    {
        $driver = (string) config($domain.'.driver');
        $drivers = config($domain.'.drivers');

        if (! is_array($drivers)) {
            throw new InvalidArgumentException(sprintf('Invalid %s driver map configuration.', $domain));
        }

        $gatewayClass = $drivers[$driver] ?? null;

        if (! is_string($gatewayClass) || $gatewayClass === '') {
            throw new InvalidArgumentException(sprintf('Unsupported %s driver [%s].', $domain, $driver));
        }

        $gateway = $this->app->make($gatewayClass);

        if (! $gateway instanceof $contract) {
            throw new InvalidArgumentException(sprintf(
                '%s driver [%s] must implement %s.',
                ucfirst($domain),
                $driver,
                $contract,
            ));
        }

        return $gateway;
    }
}
