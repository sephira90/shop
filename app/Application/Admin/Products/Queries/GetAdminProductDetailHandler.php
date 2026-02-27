<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Queries;

use App\Application\Admin\Products\Dto\AdminProductResultDto;

final class GetAdminProductDetailHandler
{
    /**
     * Execute admin product detail query.
     */
    public function handle(GetAdminProductDetailQuery $query): AdminProductResultDto
    {
        $product = $query->product->load(['category', 'variants.inventory']);

        return AdminProductResultDto::fromProduct($product);
    }
}
