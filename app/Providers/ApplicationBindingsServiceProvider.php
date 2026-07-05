<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Admin\Categories\Contracts\AdminCategoryReadRepository as AdminCategoryReadRepositoryContract;
use App\Application\Admin\Orders\Contracts\AdminOrderReadRepository as AdminOrderReadRepositoryContract;
use App\Application\Admin\Products\Contracts\AdminProductReadRepository as AdminProductReadRepositoryContract;
use App\Application\Admin\Promotions\Contracts\AdminPromotionReadRepository as AdminPromotionReadRepositoryContract;
use App\Repositories\AdminOrderReadRepository;
use App\Repositories\AdminProductReadRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\PromotionRepository;
use Illuminate\Support\ServiceProvider;

final class ApplicationBindingsServiceProvider extends ServiceProvider
{
    /**
     * Register application-layer repository bindings that do not belong to a narrower concern module.
     */
    public function register(): void
    {
        $this->app->bind(AdminOrderReadRepositoryContract::class, AdminOrderReadRepository::class);
        $this->app->bind(AdminProductReadRepositoryContract::class, AdminProductReadRepository::class);
        $this->app->bind(AdminPromotionReadRepositoryContract::class, PromotionRepository::class);
        $this->app->bind(AdminCategoryReadRepositoryContract::class, CategoryRepository::class);
    }
}
