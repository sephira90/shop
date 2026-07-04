<?php

declare(strict_types=1);

return [
    App\Providers\ApplicationBindingsServiceProvider::class,
    App\Domains\Catalog\CatalogServiceProvider::class,
    App\Providers\AuthBindingsServiceProvider::class,
    App\Providers\GatewayServiceProvider::class,
    App\Providers\MaintenanceServiceProvider::class,
    App\Providers\ObservabilityServiceProvider::class,
    App\Providers\OrdersServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\EventServiceProvider::class,
];
