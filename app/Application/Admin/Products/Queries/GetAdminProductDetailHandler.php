<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Queries;

use App\Models\Product;

final class GetAdminProductDetailHandler
{
    /**
     * Execute admin product detail query.
     */
    public function handle(GetAdminProductDetailQuery $query): Product
    {
        return $query->product->load(['category', 'variants.inventory']);
    }
}
