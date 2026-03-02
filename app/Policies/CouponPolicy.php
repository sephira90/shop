<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Coupon;
use App\Models\User;

final class CouponPolicy
{
    /**
     * Determine whether user can view coupon list.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleName::ADMIN) || $user->hasRole(RoleName::MANAGER);
    }

    /**
     * Determine whether user can view coupon.
     */
    public function view(User $user, Coupon $coupon): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether user can create coupon.
     */
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether user can update coupon.
     */
    public function update(User $user, Coupon $coupon): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether user can delete coupon.
     */
    public function delete(User $user, Coupon $coupon): bool
    {
        return $this->viewAny($user);
    }
}
