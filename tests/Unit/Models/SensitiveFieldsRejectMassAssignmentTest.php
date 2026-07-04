<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Tests\TestCase;

class SensitiveFieldsRejectMassAssignmentTest extends TestCase
{
    public function test_user_rejects_is_active_via_fill(): void
    {
        $user = new User;

        $this->expectException(MassAssignmentException::class);

        $user->fill(['is_active' => false]);
    }

    public function test_order_rejects_status_via_fill(): void
    {
        $order = new Order;

        $this->expectException(MassAssignmentException::class);

        $order->fill(['status' => 'cancelled']);
    }

    public function test_order_rejects_payment_status_via_fill(): void
    {
        $order = new Order;

        $this->expectException(MassAssignmentException::class);

        $order->fill(['payment_status' => 'captured']);
    }

    public function test_order_rejects_shipment_status_via_fill(): void
    {
        $order = new Order;

        $this->expectException(MassAssignmentException::class);

        $order->fill(['shipment_status' => 'delivered']);
    }

    public function test_payment_rejects_status_via_fill(): void
    {
        $payment = new Payment;

        $this->expectException(MassAssignmentException::class);

        $payment->fill(['status' => 'captured']);
    }

    public function test_shipment_rejects_status_via_fill(): void
    {
        $shipment = new Shipment;

        $this->expectException(MassAssignmentException::class);

        $shipment->fill(['status' => 'delivered']);
    }
}
