<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Maintenance\Contracts\MaintenanceCleanupResource;
use App\Support\Maintenance\MaintenanceCleanupPlanFactory;
use App\Support\Maintenance\Resources\ActiveCartCleanupResource;
use App\Support\Maintenance\Resources\CheckoutIdempotencyCleanupResource;
use App\Support\Maintenance\Resources\InactiveCartCleanupResource;
use App\Support\Maintenance\Resources\WebhookReceiptCleanupResource;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use UnexpectedValueException;

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
            fn (Application $app): MaintenanceCleanupPlanFactory => new MaintenanceCleanupPlanFactory(
                $this->resolveCleanupResources($app),
            ),
        );
    }

    /**
     * @return list<MaintenanceCleanupResource>
     */
    private function resolveCleanupResources(Application $app): array
    {
        $resources = [];

        foreach ($app->tagged('maintenance.cleanup.resources') as $resource) {
            if (! $resource instanceof MaintenanceCleanupResource) {
                throw new UnexpectedValueException('Maintenance cleanup resource binding must implement MaintenanceCleanupResource.');
            }

            $resources[] = $resource;
        }

        return $resources;
    }
}
