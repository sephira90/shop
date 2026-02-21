<?php

declare(strict_types=1);

use App\Infrastructure\Payments\FakePaymentGateway;

return [
    'driver' => env('PAYMENT_DRIVER', 'fake-payment'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Drivers
    |--------------------------------------------------------------------------
    |
    | Configure payment gateway implementations by driver key.
    | `PAYMENT_DRIVER` selects the active implementation.
    |
    */
    'drivers' => [
        'fake-payment' => FakePaymentGateway::class,
    ],
];
