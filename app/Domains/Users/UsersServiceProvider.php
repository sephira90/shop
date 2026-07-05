<?php

declare(strict_types=1);

namespace App\Domains\Users;

use App\Domains\Users\Contracts\AccountOrderReadRepository as AccountOrderReadRepositoryContract;
use App\Domains\Users\Contracts\AuthAuditLogger as AuthAuditLoggerContract;
use App\Domains\Users\Contracts\AuthPasswordBrokerRepository as AuthPasswordBrokerRepositoryContract;
use App\Domains\Users\Contracts\AuthUserRepository as AuthUserRepositoryContract;
use App\Domains\Users\Infrastructure\ObservabilityAuthAuditLogger;
use App\Domains\Users\Repositories\AccountOrderReadRepository;
use App\Domains\Users\Repositories\AuthPasswordBrokerRepository;
use App\Domains\Users\Repositories\AuthUserRepository;
use App\Domains\Users\Support\AuthLoginRateLimitKey;
use App\Support\Data\TypedValue;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class UsersServiceProvider extends ServiceProvider
{
    /**
     * Register Users module repository bindings.
     */
    public function register(): void
    {
        $this->app->bind(AuthUserRepositoryContract::class, AuthUserRepository::class);
        $this->app->bind(AuthPasswordBrokerRepositoryContract::class, AuthPasswordBrokerRepository::class);
        $this->app->bind(AuthAuditLoggerContract::class, ObservabilityAuthAuditLogger::class);
        $this->app->bind(AccountOrderReadRepositoryContract::class, AccountOrderReadRepository::class);
    }

    public function boot(AuthLoginRateLimitKey $rateLimitKey): void
    {
        Password::defaults(
            static fn (): Password => Password::min(12)->letters()->numbers(),
        );

        RateLimiter::for('auth.login', static function (Request $request) use ($rateLimitKey): Limit {
            $emailInput = $request->input('email');
            $email = is_string($emailInput) ? $emailInput : '';
            $maxAttempts = TypedValue::int(config('auth.login_throttle.max_attempts'));
            $decaySeconds = TypedValue::int(config('auth.login_throttle.decay_seconds'));

            return Limit::perSecond($maxAttempts, $decaySeconds)
                ->by($rateLimitKey->resolve($email, $request->ip()));
        });
    }
}
