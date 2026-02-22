<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Commands;

use App\Models\Product;

final readonly class DeleteAdminProductCommand
{
    /**
     * Create command payload for admin product delete flow.
     */
    public function __construct(
        public Product $product,
    ) {}
}
