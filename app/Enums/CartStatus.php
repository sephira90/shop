<?php

declare(strict_types=1);

namespace App\Enums;

enum CartStatus: string
{
    case ACTIVE = 'active';
    case CHECKED_OUT = 'checked_out';
    case ABANDONED = 'abandoned';
}
