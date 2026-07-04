<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Observability\Channels\EmailObservabilityAlertChannel;
use App\Support\Observability\Channels\PagerDutyObservabilityAlertChannel;
use App\Support\Observability\Channels\SlackObservabilityAlertChannel;
use App\Support\Observability\CorrelationContext;
use App\Support\Observability\ObservabilityAlertCooldownStore;
use App\Support\Observability\ObservabilityAlertMessageBuilder;
use App\Support\Observability\ObservabilityAlertRouter;
use App\Support\Observability\ObservabilityAlertRoutingLogger;
use Illuminate\Support\ServiceProvider;

final class ObservabilityServiceProvider extends ServiceProvider
{
    /**
     * Register observability bindings.
     */
    public function register(): void
    {
        $this->app->singleton(CorrelationContext::class);

        $this->app->bind(
            ObservabilityAlertRouter::class,
            fn (): ObservabilityAlertRouter => new ObservabilityAlertRouter(
                $this->app->make(ObservabilityAlertCooldownStore::class),
                $this->app->make(ObservabilityAlertMessageBuilder::class),
                $this->app->make(ObservabilityAlertRoutingLogger::class),
                [
                    $this->app->make(EmailObservabilityAlertChannel::class),
                    $this->app->make(SlackObservabilityAlertChannel::class),
                    $this->app->make(PagerDutyObservabilityAlertChannel::class),
                ],
            ),
        );
    }
}
