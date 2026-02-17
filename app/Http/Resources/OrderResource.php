<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    /**
     * Transform resource into array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'email' => $this->email,
            'status' => $this->status?->value,
            'payment_status' => $this->payment_status?->value,
            'shipment_status' => $this->shipment_status?->value,
            'currency' => $this->currency,
            'subtotal' => (float) $this->subtotal,
            'discount_total' => (float) $this->discount_total,
            'shipping_total' => (float) $this->shipping_total,
            'total' => (float) $this->total,
            'billing_address' => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            'items' => $this->items->map(static fn ($item): array => [
                'product_variant_id' => $item->product_variant_id,
                'sku' => $item->sku,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
            ])->values(),
            'payments' => $this->payments->map(static fn ($payment): array => [
                'gateway' => $payment->gateway,
                'transaction_id' => $payment->transaction_id,
                'status' => $payment->status?->value,
                'amount' => (float) $payment->amount,
            ])->values(),
            'shipments' => $this->shipments->map(static fn ($shipment): array => [
                'provider' => $shipment->provider,
                'tracking_number' => $shipment->tracking_number,
                'status' => $shipment->status?->value,
                'cost' => (float) $shipment->cost,
            ])->values(),
            'placed_at' => $this->placed_at?->toAtomString(),
            'created_at' => $this->created_at?->toAtomString(),
        ];
    }
}
