<?php

declare(strict_types=1);

namespace App\Domain\Order;

use App\Enums\PaymentStatus;
use App\Models\Order;

final readonly class OrderPaymentStatusResolver
{
    public function hasCapturedPayment(Order $order): bool
    {
        return $this->normalizePaymentStatus($order) === PaymentStatus::CAPTURED;
    }

    public function normalizePaymentStatus(Order $order): ?PaymentStatus
    {
        $rawPaymentStatus = $order->getRawOriginal('payment_status');

        if ($rawPaymentStatus instanceof PaymentStatus) {
            return $rawPaymentStatus;
        }

        if (is_string($rawPaymentStatus)) {
            return PaymentStatus::tryFrom($rawPaymentStatus);
        }

        /** @var mixed $attributePaymentStatus */
        $attributePaymentStatus = $order->getAttributes()['payment_status'] ?? null;

        if ($attributePaymentStatus instanceof PaymentStatus) {
            return $attributePaymentStatus;
        }

        if (is_string($attributePaymentStatus)) {
            return PaymentStatus::tryFrom($attributePaymentStatus);
        }

        return null;
    }
}
