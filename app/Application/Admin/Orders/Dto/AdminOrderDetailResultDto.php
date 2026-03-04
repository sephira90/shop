<?php

declare(strict_types=1);

namespace App\Application\Admin\Orders\Dto;

use App\Domain\ValueObjects\Money;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shipment;
use App\Support\Data\TypedValue;
use DateTimeInterface;
use Illuminate\Support\Collection;

final readonly class AdminOrderDetailResultDto
{
    public static function fromOrder(Order $order): self
    {
        $currency = (string) $order->currency;
        /** @var list<AdminOrderItemResultDto> $items */
        $items = [];
        if ($order->relationLoaded('items')) {
            $loadedItems = $order->getRelation('items');
            if ($loadedItems instanceof Collection) {
                foreach ($loadedItems as $item) {
                    if ($item instanceof OrderItem) {
                        $items[] = AdminOrderItemResultDto::fromOrderItem($item);
                    }
                }
            }
        }

        /** @var list<AdminOrderPaymentResultDto> $payments */
        $payments = [];
        if ($order->relationLoaded('payments')) {
            $loadedPayments = $order->getRelation('payments');
            if ($loadedPayments instanceof Collection) {
                foreach ($loadedPayments as $payment) {
                    if ($payment instanceof Payment) {
                        $payments[] = AdminOrderPaymentResultDto::fromPayment($payment);
                    }
                }
            }
        }

        /** @var list<AdminOrderShipmentResultDto> $shipments */
        $shipments = [];
        if ($order->relationLoaded('shipments')) {
            $loadedShipments = $order->getRelation('shipments');
            if ($loadedShipments instanceof Collection) {
                foreach ($loadedShipments as $shipment) {
                    if ($shipment instanceof Shipment) {
                        $shipments[] = AdminOrderShipmentResultDto::fromShipment($shipment);
                    }
                }
            }
        }

        return new self(
            id: (string) $order->id,
            orderNumber: (string) $order->order_number,
            email: (string) $order->email,
            status: TypedValue::string($order->getRawOriginal('status')),
            paymentStatus: TypedValue::string($order->getRawOriginal('payment_status')),
            shipmentStatus: TypedValue::string($order->getRawOriginal('shipment_status')),
            currency: $currency,
            subtotal: self::moneyValue($order, 'subtotal', $currency),
            discountTotal: self::moneyValue($order, 'discount_total', $currency),
            shippingTotal: self::moneyValue($order, 'shipping_total', $currency),
            total: self::moneyValue($order, 'total', $currency),
            billingAddress: self::normalizeAddress($order->billing_address),
            shippingAddress: self::normalizeAddress($order->shipping_address),
            items: $items,
            payments: $payments,
            shipments: $shipments,
            placedAt: self::formatDateLike($order->placed_at),
            createdAt: self::formatDateLike($order->created_at),
        );
    }

    /**
     * @param  list<AdminOrderItemResultDto>  $items
     * @param  list<AdminOrderPaymentResultDto>  $payments
     * @param  list<AdminOrderShipmentResultDto>  $shipments
     * @param  array<string, mixed>|null  $billingAddress
     * @param  array<string, mixed>|null  $shippingAddress
     */
    public function __construct(
        public string $id,
        public string $orderNumber,
        public string $email,
        public string $status,
        public string $paymentStatus,
        public string $shipmentStatus,
        public string $currency,
        public float $subtotal,
        public float $discountTotal,
        public float $shippingTotal,
        public float $total,
        public ?array $billingAddress,
        public ?array $shippingAddress,
        public array $items,
        public array $payments,
        public array $shipments,
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
     *     subtotal:float,
     *     discount_total:float,
     *     shipping_total:float,
     *     total:float,
     *     billing_address:array<string, mixed>|null,
     *     shipping_address:array<string, mixed>|null,
     *     items:list<array{
     *         product_variant_id:int|null,
     *         sku:string,
     *         name:string,
     *         quantity:int,
     *         unit_price:float,
     *         total_price:float
     *     }>,
     *     payments:list<array{
     *         gateway:string,
     *         transaction_id:string,
     *         status:string|null,
     *         amount:float
     *     }>,
     *     shipments:list<array{
     *         provider:string,
     *         tracking_number:string,
     *         status:string|null,
     *         cost:float
     *     }>,
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
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discountTotal,
            'shipping_total' => $this->shippingTotal,
            'total' => $this->total,
            'billing_address' => $this->billingAddress,
            'shipping_address' => $this->shippingAddress,
            'items' => array_map(
                static fn (AdminOrderItemResultDto $item): array => $item->toArray(),
                $this->items
            ),
            'payments' => array_map(
                static fn (AdminOrderPaymentResultDto $payment): array => $payment->toArray(),
                $this->payments
            ),
            'shipments' => array_map(
                static fn (AdminOrderShipmentResultDto $shipment): array => $shipment->toArray(),
                $this->shipments
            ),
            'placed_at' => $this->placedAt,
            'created_at' => $this->createdAt,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function normalizeAddress(mixed $value): ?array
    {
        return TypedValue::associativeArrayOrNull($value);
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

    private static function moneyValue(Order $order, string $field, string $currency): float
    {
        $rawValue = $order->getRawOriginal($field);

        if (is_string($rawValue) || is_int($rawValue) || is_float($rawValue)) {
            return Money::fromDecimal($rawValue, $currency)->toFloat();
        }

        /** @var mixed $attributeValue */
        $attributeValue = $order->getAttribute($field);

        return Money::fromDecimal(TypedValue::float($attributeValue), $currency)->toFloat();
    }
}
