<?php

declare(strict_types=1);

use App\Infrastructure\Shipping\FakeShippingGateway;

return [
    'driver' => env('SHIPPING_DRIVER', 'fake-shipping'),

    /*
    |--------------------------------------------------------------------------
    | Shipping Gateway Drivers
    |--------------------------------------------------------------------------
    |
    | Configure shipping gateway implementations by driver key.
    | `SHIPPING_DRIVER` selects the active implementation.
    |
    */
    'drivers' => [
        'fake-shipping' => FakeShippingGateway::class,
    ],
];
