<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Maintenance\MaintenanceCleanupPlanFactory;
use App\Support\Maintenance\Resources\ActiveCartCleanupResource;
use App\Support\Maintenance\Resources\CheckoutIdempotencyCleanupResource;
use App\Support\Maintenance\Resources\InactiveCartCleanupResource;
use App\Support\Maintenance\Resources\WebhookReceiptCleanupResource;
use Illuminate\Support\ServiceProvider;

final class MaintenanceServiceProvider extends ServiceProvider
{
    /**
     * Register maintenance cleanup resources and orchestration bindings.
     */
    public function register(): void
    {
        $this->app->tag([
            CheckoutIdempotencyCleanupResource::class,
            WebhookReceiptCleanupResource::class,
            ActiveCartCleanupResource::class,
            InactiveCartCleanupResource::class,
        ], 'maintenance.cleanup.resources');

        $this->app->bind(
            MaintenanceCleanupPlanFactory::class,
            fn ($app): MaintenanceCleanupPlanFactory => new MaintenanceCleanupPlanFactory(
                $app->tagged('maintenance.cleanup.resources'),
            ),
        );
    }
}
