<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class CartPolicy
{
    /**
     * Allow reading current cart for authenticated and guest flows.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Allow cart mutations only for authenticated users or guests with explicit token.
     */
    public function modify(?User $user, ?string $guestToken = null): bool
    {
        if ($user instanceof User) {
            return true;
        }

        return $this->normalizeGuestToken($guestToken) !== null;
    }

    private function normalizeGuestToken(?string $guestToken): ?string
    {
        if (! is_string($guestToken)) {
            return null;
        }

        $token = trim($guestToken);

        return $token !== '' ? $token : null;
    }
}
