<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Order;
use App\Models\User;

final class OrderRepository
{
    /**
     * Get user orders with eager loading.
     */
    public function paginateForUser(User $user, int $perPage = 20)
    {
        return Order::query()
            ->with(['items', 'payments', 'shipments'])
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * Get all orders for admin area.
     */
    public function paginateForAdmin(int $perPage = 30)
    {
        return Order::query()
            ->with(['user', 'items', 'payments', 'shipments'])
            ->latest('created_at')
            ->paginate($perPage);
    }
}
