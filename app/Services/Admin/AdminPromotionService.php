<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Promotion;

final class AdminPromotionService
{
    /**
     * Create promotion entry.
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): Promotion
    {
        return Promotion::query()->create($payload);
    }
}
