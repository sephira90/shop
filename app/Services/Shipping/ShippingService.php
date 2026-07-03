<?php

declare(strict_types=1);

namespace App\Services\Shipping;

use App\Contracts\ShippingGatewayInterface;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Webhook\WebhookProcessingPipeline;
use App\Support\Data\JsonPayload;
use App\Support\Data\TypedValue;
use DomainException;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($order): Shipment {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder instanceof Order) {
                throw new DomainException('Order not found for shipment dispatch.');
            }

            $existingShipment = Shipment::query()
                ->where('order_id', $lockedOrder->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($existingShipment instanceof Shipment) {
                return $existingShipment;
            }

            $shippingDriver = $this->shippingDriver();
            $result = $this->gateway->createShipment($lockedOrder);

            return Shipment::query()->create([
                'order_id' => $lockedOrder->id,
                'provider' => $shippingDriver,
                'tracking_number' => $result->trackingNumber,
                'status' => $result->status->value,
                'cost' => $result->cost,
                'payload' => $result->payload->toArray(),
            ]);
        });
    }

    /**
     * Process shipping webhook payload.
     */
    public function processWebhook(
        JsonPayload $payload,
        string $signature,
        ?string $receivedAtIso8601 = null,
        string $source = 'runtime',
        ?string $prevalidatedEventId = null,
        ?string $correlationId = null,
    ): void {
        $this->webhookProcessingPipeline->process(
            $this->shippingWebhookAdapter,
            $payload,
            $signature,
            $receivedAtIso8601,
            $source,
            $prevalidatedEventId,
            $correlationId,
        );
    }

    private function shippingDriver(): string
    {
        return TypedValue::string(config('shipping.driver', 'fake-shipping'));
    }
}
