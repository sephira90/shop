<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Admin\Categories\Contracts\AdminCategoryReadRepository as AdminCategoryReadRepositoryContract;
use App\Application\Admin\Orders\Contracts\AdminOrderReadRepository as AdminOrderReadRepositoryContract;
use App\Application\Admin\Products\Contracts\AdminProductReadRepository as AdminProductReadRepositoryContract;
use App\Application\Admin\Promotions\Contracts\AdminPromotionReadRepository as AdminPromotionReadRepositoryContract;
use App\Application\Catalog\Contracts\CatalogProductReadRepository as CatalogProductReadRepositoryContract;
use App\Repositories\AdminOrderReadRepository;
use App\Repositories\AdminProductReadRepository;
use App\Repositories\CatalogProductReadRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\PromotionRepository;
use Tests\TestCase;

final class ApplicationRepositoryBindingTest extends TestCase
{
    public function test_admin_order_read_repository_contract_is_bound_to_default_implementation(): void
    {
        $this->assertInstanceOf(AdminOrderReadRepository::class, $this->app->make(AdminOrderReadRepositoryContract::class));
    }

    public function test_admin_product_read_repository_contract_is_bound_to_default_implementation(): void
    {
        $this->assertInstanceOf(AdminProductReadRepository::class, $this->app->make(AdminProductReadRepositoryContract::class));
    }

    public function test_admin_promotion_read_repository_contract_is_bound_to_default_implementation(): void
    {
        $this->assertInstanceOf(PromotionRepository::class, $this->app->make(AdminPromotionReadRepositoryContract::class));
    }

    public function test_admin_category_read_repository_contract_is_bound_to_default_implementation(): void
    {
        $this->assertInstanceOf(CategoryRepository::class, $this->app->make(AdminCategoryReadRepositoryContract::class));
    }

    public function test_catalog_product_read_repository_contract_is_bound_to_default_implementation(): void
    {
        $this->assertInstanceOf(CatalogProductReadRepository::class, $this->app->make(CatalogProductReadRepositoryContract::class));
    }
}
