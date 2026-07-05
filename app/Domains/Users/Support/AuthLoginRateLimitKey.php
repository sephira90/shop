<?php

declare(strict_types=1);

namespace App\Domains\Users\Support;

use Illuminate\Support\Str;

final readonly class AuthLoginRateLimitKey
{
    public function resolve(string $email, ?string $clientIp): string
    {
        $normalizedEmail = Str::lower(trim($email));
        $normalizedClientIp = trim($clientIp ?? 'unknown');

        if ($normalizedClientIp === '') {
            $normalizedClientIp = 'unknown';
        }

        return hash('sha256', $normalizedEmail).'|'.$normalizedClientIp;
    }
}
