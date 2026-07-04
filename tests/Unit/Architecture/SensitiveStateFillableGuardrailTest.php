<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use Tests\TestCase;

class SensitiveStateFillableGuardrailTest extends TestCase
{
    public function test_user_is_active_is_not_mass_assignable(): void
    {
        $this->assertNotContains(
            'is_active',
            (new User)->getFillable(),
            'User::is_active is a privilege flag and must stay outside $fillable; mutate only through explicit activation paths.'
        );
    }

    public function test_order_status_is_not_mass_assignable(): void
    {
        $this->assertNotContains(
            'status',
            (new Order)->getFillable(),
            'Order::status is a state field governed by transition policies and must stay outside $fillable.'
        );
    }

    public function test_order_payment_status_is_not_mass_assignable(): void
    {
        $this->assertNotContains(
            'payment_status',
            (new Order)->getFillable(),
            'Order::payment_status is a state field governed by transition policies and must stay outside $fillable.'
        );
    }

    public function test_order_shipment_status_is_not_mass_assignable(): void
    {
        $this->assertNotContains(
            'shipment_status',
            (new Order)->getFillable(),
            'Order::shipment_status is a state field governed by transition policies and must stay outside $fillable.'
        );
    }

    public function test_payment_status_is_not_mass_assignable(): void
    {
        $this->assertNotContains(
            'status',
            (new Payment)->getFillable(),
            'Payment::status is a state field governed by transition policies and must stay outside $fillable.'
        );
    }

    public function test_shipment_status_is_not_mass_assignable(): void
    {
        $this->assertNotContains(
            'status',
            (new Shipment)->getFillable(),
            'Shipment::status is a state field governed by transition policies and must stay outside $fillable.'
        );
    }
}
