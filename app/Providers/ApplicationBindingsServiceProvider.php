<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Account\Orders\Contracts\AccountOrderReadRepository as AccountOrderReadRepositoryContract;
use App\Repositories\AccountOrderReadRepository;
use Illuminate\Support\ServiceProvider;

final class ApplicationBindingsServiceProvider extends ServiceProvider
{
    /**
     * Register application-layer repository bindings that do not belong to a narrower concern module.
     */
    public function register(): void
    {
        $this->app->bind(AccountOrderReadRepositoryContract::class, AccountOrderReadRepository::class);
    }
}
