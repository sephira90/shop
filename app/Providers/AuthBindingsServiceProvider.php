<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Auth\Contracts\AuthPasswordBrokerRepository as AuthPasswordBrokerRepositoryContract;
use App\Application\Auth\Contracts\AuthUserRepository as AuthUserRepositoryContract;
use App\Repositories\AuthPasswordBrokerRepository;
use App\Repositories\AuthUserRepository;
use Illuminate\Support\ServiceProvider;

final class AuthBindingsServiceProvider extends ServiceProvider
{
    /**
     * Register authentication repository bindings.
     */
    public function register(): void
    {
        $this->app->bind(AuthUserRepositoryContract::class, AuthUserRepository::class);
        $this->app->bind(AuthPasswordBrokerRepositoryContract::class, AuthPasswordBrokerRepository::class);
    }
}
