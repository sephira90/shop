<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleName::ADMIN) || $user->hasRole(RoleName::MANAGER);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->hasRole(RoleName::ADMIN)
            || $user->hasRole(RoleName::MANAGER)
            || $order->user_id === $user->id;
    }

    /**
     * Determine whether the user can update order status.
     */
    public function update(User $user, Order $order): bool
    {
        return $user->hasRole(RoleName::ADMIN) || $user->hasRole(RoleName::MANAGER);
    }
}
