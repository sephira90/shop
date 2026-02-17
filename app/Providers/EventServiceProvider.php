<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\OrderPlaced;
use App\Listeners\QueueOrderSideEffects;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<string, list<string>>
     */
    protected $listen = [
        OrderPlaced::class => [
            QueueOrderSideEffects::class,
        ],
    ];
}
