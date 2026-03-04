<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Application\Checkout\Dto\CheckoutOrderWriteInputDto;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;

final class CheckoutOrderWriter
{
    public function write(CheckoutOrderWriteInputDto $input): Order
    {
        $subtotal = $input->cartPreparation->subtotal;
        $total = $subtotal
            ->subtract($input->discountTotal)
            ->add($input->shippingTotal);

        $order = Order::query()->create([
            'order_number' => 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
            'user_id' => $input->user?->id,
            'email' => $input->checkoutInput->email,
            'status' => OrderStatus::PENDING->value,
            'payment_status' => PaymentStatus::PENDING->value,
            'shipment_status' => ShipmentStatus::PENDING->value,
            'currency' => $input->checkoutInput->currency,
            'subtotal' => $subtotal->toFloat(),
            'discount_total' => $input->discountTotal->toFloat(),
            'shipping_total' => $input->shippingTotal->toFloat(),
            'total' => $total->toFloat(),
            'billing_address' => $input->checkoutInput->billingAddress->toArray(),
            'shipping_address' => $input->checkoutInput->shippingAddress->toArray(),
            'cart_snapshot' => $input->cartPreparation->toCartSnapshot(),
            'placed_at' => now(),
        ]);

        $timestamp = now()->toDateTimeString();
        $orderItems = $input->cartPreparation->toOrderItemInsertRows(
            orderId: $order->id,
            cartId: $input->cart->id,
            timestamp: $timestamp,
        );

        if ($orderItems !== []) {
            OrderItem::query()->insert($orderItems);
        }

        return $order;
    }
}
