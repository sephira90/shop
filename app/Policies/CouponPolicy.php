<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Coupon;
use App\Models\User;

class CouponPolicy
{
    /**
     * Determine whether user can update coupon.
     */
    public function update(User $user, Coupon $coupon): bool
    {
        return $user->hasRole(RoleName::ADMIN) || $user->hasRole(RoleName::MANAGER);
    }
}
