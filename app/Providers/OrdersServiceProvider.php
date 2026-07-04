<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Orders\FailedJobsDetector;
use App\Support\Orders\OrdersReconcileRunner;
use App\Support\Orders\StalePendingPaymentDetector;
use App\Support\Orders\StuckShipmentDetector;
use Illuminate\Support\ServiceProvider;

final class OrdersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            OrdersReconcileRunner::class,
            fn (): OrdersReconcileRunner => new OrdersReconcileRunner([
                $this->app->make(StuckShipmentDetector::class),
                $this->app->make(StalePendingPaymentDetector::class),
                $this->app->make(FailedJobsDetector::class),
            ]),
        );
    }
}
