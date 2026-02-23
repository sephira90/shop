<?php

declare(strict_types=1);

namespace App\Services\Shipping;

use App\Contracts\ShippingGatewayInterface;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Webhook\WebhookProcessingPipeline;

final readonly class ShippingService
{
    /**
     * Create shipping service.
     */
    public function __construct(
        private ShippingGatewayInterface $gateway,
        private WebhookProcessingPipeline $webhookProcessingPipeline,
        private ShippingWebhookAdapter $shippingWebhookAdapter,
    ) {}

    /**
     * Create shipment for order.
     */
    public function createShipment(Order $order): Shipment
    {
        $shippingDriver = $this->shippingDriver();
        $result = $this->gateway->createShipment($order);

        return Shipment::query()->create([
            'order_id' => $order->id,
            'provider' => $shippingDriver,
            'tracking_number' => $result['tracking_number'],
            'status' => $result['status']->value,
            'cost' => $result['cost'],
            'payload' => $result['payload'],
        ]);
    }

    /**
     * Process shipping webhook payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function processWebhook(
        array $payload,
        string $signature,
        ?string $receivedAtIso8601 = null,
        string $source = 'runtime',
    ): void {
        $this->webhookProcessingPipeline->process(
            $this->shippingWebhookAdapter,
            $payload,
            $signature,
            $receivedAtIso8601,
            $source,
        );
    }

    private function shippingDriver(): string
    {
        return (string) config('shipping.driver', 'fake-shipping');
    }
}
