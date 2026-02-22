<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Commands;

use App\Models\Product;

final readonly class UpdateAdminProductCommand
{
    /**
     * Create command payload for admin product update flow.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public Product $product,
        public array $payload,
    ) {}
}
