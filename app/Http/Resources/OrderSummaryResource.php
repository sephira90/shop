<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderSummaryResource extends JsonResource
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
            'status' => (string) $this->getRawOriginal('status'),
            'payment_status' => (string) $this->getRawOriginal('payment_status'),
            'shipment_status' => (string) $this->getRawOriginal('shipment_status'),
            'currency' => $this->currency,
            'total' => (float) $this->total,
            'placed_at' => $this->getRawOriginal('placed_at'),
            'created_at' => $this->getRawOriginal('created_at'),
        ];
    }
}
