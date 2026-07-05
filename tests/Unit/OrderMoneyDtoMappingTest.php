<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Admin\Orders\Dto\AdminOrderDetailResultDto;
use App\Application\Checkout\Dto\CheckoutOrderResultDto;
use App\Domains\Users\Application\Dto\AccountOrderDetailResultDto;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderMoneyDtoMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_detail_dtos_map_money_fields_consistently_on_float_boundary(): void
    {
        $order = Order::unguarded(fn (): Order => Order::query()->create([
            'order_number' => 'ORD-MONEY-MAP-001',
            'email' => 'money-map@example.com',
            'status' => 'paid',
            'payment_status' => 'captured',
            'shipment_status' => 'pending',
            'currency' => 'USD',
            'subtotal' => '0.10',
            'discount_total' => '0.00',
            'shipping_total' => '0.20',
            'total' => '0.30',
            'billing_address' => ['line1' => '1 Main Street'],
            'shipping_address' => ['line1' => '1 Main Street'],
            'cart_snapshot' => ['items' => []],
            'placed_at' => now(),
        ]));

        $checkout = CheckoutOrderResultDto::fromOrder($order)->toArray();
        $admin = AdminOrderDetailResultDto::fromOrder($order)->toArray();
        $account = AccountOrderDetailResultDto::fromOrder($order)->toArray();

        $this->assertSame(0.1, $checkout['subtotal']);
        $this->assertSame(0.2, $checkout['shipping_total']);
        $this->assertSame(0.3, $checkout['total']);

        $this->assertSame(0.1, $admin['subtotal']);
        $this->assertSame(0.2, $admin['shipping_total']);
        $this->assertSame(0.3, $admin['total']);

        $this->assertSame(0.1, $account['subtotal']);
        $this->assertSame(0.2, $account['shipping_total']);
        $this->assertSame(0.3, $account['total']);
    }
}
