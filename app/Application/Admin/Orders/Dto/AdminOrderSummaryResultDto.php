<?php

declare(strict_types=1);

namespace App\Application\Admin\Orders\Dto;

use App\Models\Order;
use App\Support\Data\TypedValue;
use DateTimeInterface;

final readonly class AdminOrderSummaryResultDto
{
    public static function fromOrder(Order $order): self
    {
        return new self(
            id: (string) $order->id,
            orderNumber: (string) $order->order_number,
            email: (string) $order->email,
            status: TypedValue::string($order->getRawOriginal('status')),
            paymentStatus: TypedValue::string($order->getRawOriginal('payment_status')),
            shipmentStatus: TypedValue::string($order->getRawOriginal('shipment_status')),
            currency: (string) $order->currency,
            total: (float) $order->total,
            placedAt: self::formatDateLike($order->placed_at),
            createdAt: self::formatDateLike($order->created_at),
        );
    }

    public function __construct(
        public string $id,
        public string $orderNumber,
        public string $email,
        public string $status,
        public string $paymentStatus,
        public string $shipmentStatus,
        public string $currency,
        public float $total,
        public ?string $placedAt,
        public ?string $createdAt,
    ) {}

    /**
     * @return array{
     *     id:string,
     *     order_number:string,
     *     email:string,
     *     status:string,
     *     payment_status:string,
     *     shipment_status:string,
     *     currency:string,
     *     total:float,
     *     placed_at:string|null,
     *     created_at:string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->orderNumber,
            'email' => $this->email,
            'status' => $this->status,
            'payment_status' => $this->paymentStatus,
            'shipment_status' => $this->shipmentStatus,
            'currency' => $this->currency,
            'total' => $this->total,
            'placed_at' => $this->placedAt,
            'created_at' => $this->createdAt,
        ];
    }

    private static function formatDateLike(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return $value;
    }
}
