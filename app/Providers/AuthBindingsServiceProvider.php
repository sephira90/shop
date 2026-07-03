<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Auth\Contracts\AuthAuditLogger as AuthAuditLoggerContract;
use App\Application\Auth\Contracts\AuthPasswordBrokerRepository as AuthPasswordBrokerRepositoryContract;
use App\Application\Auth\Contracts\AuthUserRepository as AuthUserRepositoryContract;
use App\Application\Auth\Support\AuthLoginRateLimitKey;
use App\Infrastructure\Auth\ObservabilityAuthAuditLogger;
use App\Repositories\AuthPasswordBrokerRepository;
use App\Repositories\AuthUserRepository;
use App\Support\Data\TypedValue;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AuthBindingsServiceProvider extends ServiceProvider
{
    /**
     * Register authentication repository bindings.
     */
    public function register(): void
    {
        $this->app->bind(AuthUserRepositoryContract::class, AuthUserRepository::class);
        $this->app->bind(AuthPasswordBrokerRepositoryContract::class, AuthPasswordBrokerRepository::class);
        $this->app->bind(AuthAuditLoggerContract::class, ObservabilityAuthAuditLogger::class);
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
