<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\PaymentStatus;
use App\Services\Payment\PaymentStatusTransitionPolicy;
use PHPUnit\Framework\TestCase;

class PaymentStatusTransitionPolicyTest extends TestCase
{
    /**
     * Ensure payment transition matrix is deterministic.
     */
    public function test_payment_status_transition_matrix_is_stable(): void
    {
        $policy = new PaymentStatusTransitionPolicy;

        $allowedTransitions = [
            PaymentStatus::PENDING->value => [
                PaymentStatus::PENDING,
                PaymentStatus::AUTHORIZED,
                PaymentStatus::CAPTURED,
                PaymentStatus::FAILED,
            ],
            PaymentStatus::AUTHORIZED->value => [
                PaymentStatus::AUTHORIZED,
                PaymentStatus::CAPTURED,
                PaymentStatus::FAILED,
            ],
            PaymentStatus::CAPTURED->value => [
                PaymentStatus::CAPTURED,
                PaymentStatus::REFUNDED,
            ],
            PaymentStatus::FAILED->value => [
                PaymentStatus::FAILED,
            ],
            PaymentStatus::REFUNDED->value => [
                PaymentStatus::REFUNDED,
            ],
        ];

        foreach (PaymentStatus::cases() as $from) {
            foreach (PaymentStatus::cases() as $to) {
                $expected = in_array($to, $allowedTransitions[$from->value], true);

                self::assertSame(
                    $expected,
                    $policy->canTransition($from, $to),
                    sprintf('Unexpected payment transition "%s" -> "%s".', $from->value, $to->value),
                );
            }
        }
    }
}
