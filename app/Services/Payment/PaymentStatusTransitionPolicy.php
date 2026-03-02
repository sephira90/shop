<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\PaymentStatus;

final readonly class PaymentStatusTransitionPolicy
{
    /**
     * Validate allowed payment status transition.
     */
    public function canTransition(PaymentStatus $from, PaymentStatus $to): bool
    {
        return match ($from) {
            PaymentStatus::PENDING => in_array($to, [
                PaymentStatus::PENDING,
                PaymentStatus::AUTHORIZED,
                PaymentStatus::CAPTURED,
                PaymentStatus::FAILED,
            ], true),
            PaymentStatus::AUTHORIZED => in_array($to, [
                PaymentStatus::AUTHORIZED,
                PaymentStatus::CAPTURED,
                PaymentStatus::FAILED,
            ], true),
            PaymentStatus::CAPTURED => in_array($to, [
                PaymentStatus::CAPTURED,
                PaymentStatus::REFUNDED,
            ], true),
            PaymentStatus::FAILED, PaymentStatus::REFUNDED => $to === $from,
        };
    }
}
