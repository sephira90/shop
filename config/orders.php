<?php

declare(strict_types=1);

use App\Enums\OrderStatus;

return [

    /*
    |--------------------------------------------------------------------------
    | Order Status Notifications
    |--------------------------------------------------------------------------
    |
    | Configure customer-facing order status milestones that should trigger
    | asynchronous email notifications. Values must map to OrderStatus enum
    | entries and are validated during listener resolution.
    |
    */

    'status_notifications' => [
        'notifiable_statuses' => [
            OrderStatus::SHIPPED->value,
            OrderStatus::COMPLETED->value,
            OrderStatus::CANCELLED->value,
            OrderStatus::REFUNDED->value,
        ],
    ],
];
