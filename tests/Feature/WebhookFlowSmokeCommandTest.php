<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookFlowSmokeCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure webhook flow smoke command validates end-to-end chain.
     */
    public function test_webhook_flow_smoke_command_passes(): void
    {
        $this->artisan('app:webhook-flow-smoke')
            ->assertSuccessful()
            ->expectsOutputToContain('Webhook flow smoke checks passed.');

        $order = Order::query()->latest('id')->first();
        $shipment = Shipment::query()->latest('id')->first();

        $this->assertInstanceOf(Order::class, $order);
        $this->assertInstanceOf(Shipment::class, $shipment);
        $this->assertSame('completed', (string) $order->getRawOriginal('status'));
        $this->assertSame('captured', (string) $order->getRawOriginal('payment_status'));
        $this->assertSame('delivered', (string) $order->getRawOriginal('shipment_status'));
        $this->assertSame('delivered', (string) $shipment->getRawOriginal('status'));
    }

    /**
     * Ensure production execution does not persist smoke data by default.
     */
    public function test_webhook_flow_smoke_command_rolls_back_data_in_production(): void
    {
        config()->set('app.env', 'production');

        $this->artisan('app:webhook-flow-smoke')
            ->assertSuccessful()
            ->expectsOutputToContain('Production safeguard: smoke data rolled back.');

        $this->assertDatabaseMissing('users', ['email' => 'smoke.user@shop.local']);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('shipments', 0);
        $this->assertDatabaseCount('payments', 0);
    }
}
