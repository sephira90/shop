<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determine whether user can view category list.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleName::ADMIN) || $user->hasRole(RoleName::MANAGER);
    }

    /**
     * Determine whether user can view one category.
     */
    public function view(User $user, Category $category): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether user can create category.
     */
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether user can update category.
     */
    public function update(User $user, Category $category): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether user can delete category.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->hasRole(RoleName::ADMIN);
    }
}
