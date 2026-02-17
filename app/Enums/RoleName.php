<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleName: string
{
    case CUSTOMER = 'customer';
    case MANAGER = 'manager';
    case ADMIN = 'admin';

    /**
     * Return default display name.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::CUSTOMER => 'Customer',
            self::MANAGER => 'Manager',
            self::ADMIN => 'Administrator',
        };
    }
}
