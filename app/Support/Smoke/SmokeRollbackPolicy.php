<?php

declare(strict_types=1);

namespace App\Support\Smoke;

final class SmokeRollbackPolicy
{
    public function shouldRollback(bool $persist): bool
    {
        return (string) config('app.env') === 'production' && ! $persist;
    }

    public function warningMessage(bool $rolledBack): ?string
    {
        if (! $rolledBack) {
            return null;
        }

        return 'Production safeguard: smoke data rolled back. Use --persist to keep records.';
    }
}
