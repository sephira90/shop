<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Promotion;
use App\Models\User;

class PromotionPolicy
{
    /**
     * Determine whether user can view promotion list.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleName::ADMIN) || $user->hasRole(RoleName::MANAGER);
    }

    /**
     * Determine whether user can view one promotion.
     */
    public function view(User $user, Promotion $promotion): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether user can create promotion.
     */
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether user can update promotion.
     */
    public function update(User $user, Promotion $promotion): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether user can delete promotion.
     */
    public function delete(User $user, Promotion $promotion): bool
    {
        return $this->viewAny($user);
    }
}
