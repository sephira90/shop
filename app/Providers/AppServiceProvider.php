<?php

declare(strict_types=1);

namespace App\Providers;

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

class AppServiceProvider extends ServiceProvider
{
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

        RateLimiter::for('checkout', static function (Request $request): Limit {
            $user = $request->user();

            return Limit::perMinute(6)
                ->by($user === null ? $request->ip() : (string) $user->id);
        });

        RateLimiter::for('webhook', static fn (Request $request): Limit => Limit::perMinute(120)
            ->by($request->ip()));

        RateLimiter::for('search', static function (Request $request): Limit {
            $user = $request->user();

            return Limit::perMinute(90)
                ->by($user === null ? $request->ip() : (string) $user->id);
        });
    }
}
