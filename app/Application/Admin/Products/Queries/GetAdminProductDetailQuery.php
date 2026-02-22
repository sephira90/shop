<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Queries;

use App\Models\Product;

final readonly class GetAdminProductDetailQuery
{
    /**
     * Create query payload for admin product detail.
     */
    public function __construct(
        public Product $product,
    ) {}
}
