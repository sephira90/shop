<?php

declare(strict_types=1);

namespace App\Application\Account\Orders\Dto;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shipment;
use App\Support\Data\TypedValue;
use DateTimeInterface;
use Illuminate\Support\Collection;

final readonly class AccountOrderDetailResultDto
{
    public static function fromOrder(Order $order): self
    {
        /** @var list<AccountOrderItemResultDto> $items */
        $items = [];
        if ($order->relationLoaded('items')) {
            $loadedItems = $order->getRelation('items');
            if ($loadedItems instanceof Collection) {
                foreach ($loadedItems as $item) {
                    if ($item instanceof OrderItem) {
                        $items[] = AccountOrderItemResultDto::fromOrderItem($item);
                    }
                }
            }
        }

        /** @var list<AccountOrderPaymentResultDto> $payments */
        $payments = [];
        if ($order->relationLoaded('payments')) {
            $loadedPayments = $order->getRelation('payments');
            if ($loadedPayments instanceof Collection) {
                foreach ($loadedPayments as $payment) {
                    if ($payment instanceof Payment) {
                        $payments[] = AccountOrderPaymentResultDto::fromPayment($payment);
                    }
                }
            }
        }

        /** @var list<AccountOrderShipmentResultDto> $shipments */
        $shipments = [];
        if ($order->relationLoaded('shipments')) {
            $loadedShipments = $order->getRelation('shipments');
            if ($loadedShipments instanceof Collection) {
                foreach ($loadedShipments as $shipment) {
                    if ($shipment instanceof Shipment) {
                        $shipments[] = AccountOrderShipmentResultDto::fromShipment($shipment);
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
            currency: (string) $order->currency,
            subtotal: (float) $order->subtotal,
            discountTotal: (float) $order->discount_total,
            shippingTotal: (float) $order->shipping_total,
            total: (float) $order->total,
            billingAddress: AccountOrderAddressResultDto::fromPayload($order->billing_address),
            shippingAddress: AccountOrderAddressResultDto::fromPayload($order->shipping_address),
            items: $items,
            payments: $payments,
            shipments: $shipments,
            placedAt: self::formatDateLike($order->placed_at),
            createdAt: self::formatDateLike($order->created_at),
        );
    }

    /**
     * @param  list<AccountOrderItemResultDto>  $items
     * @param  list<AccountOrderPaymentResultDto>  $payments
     * @param  list<AccountOrderShipmentResultDto>  $shipments
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
        public ?AccountOrderAddressResultDto $billingAddress,
        public ?AccountOrderAddressResultDto $shippingAddress,
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
     *     billing_address:array{line1:string|null,city:string|null,country:string|null,postcode:string|null}|null,
     *     shipping_address:array{line1:string|null,city:string|null,country:string|null,postcode:string|null}|null,
     *     items:list<array{
     *         product_variant_id:int|null,
     *         sku:string,
     *         name:string,
     *         quantity:int,
     *         unit_price:float,
     *         total_price:float
     *     }>,
     *     payments:list<array{gateway:string,transaction_id:string,status:string|null,amount:float}>,
     *     shipments:list<array{provider:string,tracking_number:string,status:string|null,cost:float}>,
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
            'billing_address' => $this->billingAddress?->toArray(),
            'shipping_address' => $this->shippingAddress?->toArray(),
            'items' => array_map(
                static fn (AccountOrderItemResultDto $item): array => $item->toArray(),
                $this->items
            ),
            'payments' => array_map(
                static fn (AccountOrderPaymentResultDto $payment): array => $payment->toArray(),
                $this->payments
            ),
            'shipments' => array_map(
                static fn (AccountOrderShipmentResultDto $shipment): array => $shipment->toArray(),
                $this->shipments
            ),
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
