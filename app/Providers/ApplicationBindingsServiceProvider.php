<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Account\Orders\Contracts\AccountOrderReadRepository as AccountOrderReadRepositoryContract;
use App\Application\Admin\Categories\Contracts\AdminCategoryReadRepository as AdminCategoryReadRepositoryContract;
use App\Application\Admin\Orders\Contracts\AdminOrderReadRepository as AdminOrderReadRepositoryContract;
use App\Application\Admin\Products\Contracts\AdminProductReadRepository as AdminProductReadRepositoryContract;
use App\Application\Admin\Promotions\Contracts\AdminPromotionReadRepository as AdminPromotionReadRepositoryContract;
use App\Application\Catalog\Contracts\CatalogProductReadRepository as CatalogProductReadRepositoryContract;
use App\Contracts\CartMutationServiceInterface;
use App\Contracts\CartServiceInterface;
use App\Contracts\CheckoutServiceInterface;
use App\Repositories\AccountOrderReadRepository;
use App\Repositories\AdminOrderReadRepository;
use App\Repositories\AdminProductReadRepository;
use App\Repositories\CatalogProductReadRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\PromotionRepository;
use App\Services\Cart\CartMutationService;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Checkout\CheckoutShippingCostResolver;
use App\Services\Checkout\FreeCheckoutShippingCostResolver;
use Illuminate\Support\ServiceProvider;

final class ApplicationBindingsServiceProvider extends ServiceProvider
{
    /**
     * Register application-layer repository bindings that do not belong to a narrower concern module.
     */
    public function register(): void
    {
        $this->app->bind(AccountOrderReadRepositoryContract::class, AccountOrderReadRepository::class);
        $this->app->bind(AdminOrderReadRepositoryContract::class, AdminOrderReadRepository::class);
        $this->app->bind(AdminProductReadRepositoryContract::class, AdminProductReadRepository::class);
        $this->app->bind(AdminPromotionReadRepositoryContract::class, PromotionRepository::class);
        $this->app->bind(AdminCategoryReadRepositoryContract::class, CategoryRepository::class);
        $this->app->bind(CatalogProductReadRepositoryContract::class, CatalogProductReadRepository::class);
        $this->app->bind(CheckoutShippingCostResolver::class, FreeCheckoutShippingCostResolver::class);
        $this->app->bind(CartMutationServiceInterface::class, CartMutationService::class);
        $this->app->bind(CartServiceInterface::class, CartService::class);
        $this->app->bind(CheckoutServiceInterface::class, CheckoutService::class);
    }
}
