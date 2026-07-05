<?php

declare(strict_types=1);

namespace App\Domains\Cart;

use App\Domains\Cart\Contracts\CartMutationServiceInterface;
use App\Domains\Cart\Contracts\CartServiceInterface;
use App\Domains\Cart\Policies\CartPolicy;
use App\Domains\Cart\Services\CartMutationService;
use App\Domains\Cart\Services\CartService;
use App\Models\Cart;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class CartServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CartServiceInterface::class, CartService::class);
        $this->app->bind(CartMutationServiceInterface::class, CartMutationService::class);
    }

    public function boot(): void
    {
        Gate::policy(Cart::class, CartPolicy::class);
    }
}
