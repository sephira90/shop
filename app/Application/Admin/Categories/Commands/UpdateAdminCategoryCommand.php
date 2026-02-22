<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Commands;

use App\Models\Category;

final readonly class UpdateAdminCategoryCommand
{
    /**
     * Create command payload for admin category update flow.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public Category $category,
        public array $payload,
    ) {}
}
