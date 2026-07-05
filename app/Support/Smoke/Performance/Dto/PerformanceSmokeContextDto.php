<?php

declare(strict_types=1);

namespace App\Support\Smoke\Performance\Dto;

use App\Application\Admin\Orders\Dto\AdminOrderListFilterDto;
use App\Application\Admin\Products\Dto\AdminProductListFilterDto;
use App\Domains\Catalog\Contracts\Dto\CatalogProductListFilterDto;
use App\Domains\Checkout\Application\Dto\CheckoutPlaceOrderInputDto;

final readonly class PerformanceSmokeContextDto
{
    public function __construct(
        public CatalogProductListFilterDto $catalogFilter,
        public string $cartGuestToken,
        public string $checkoutGuestToken,
        public CheckoutPlaceOrderInputDto $checkoutPayload,
        public AdminOrderListFilterDto $orderFilter,
        public AdminProductListFilterDto $productFilter,
    ) {}
}
