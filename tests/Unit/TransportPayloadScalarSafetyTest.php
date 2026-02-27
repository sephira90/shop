<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Services\Payment\Dto\PaymentCreationResultDto;
use App\Services\Shipping\Dto\ShipmentCreationResultDto;
use App\Support\Data\JsonPayload;
use PHPUnit\Framework\TestCase;

final class TransportPayloadScalarSafetyTest extends TestCase
{
    public function test_payment_creation_result_to_array_exposes_scalar_status(): void
    {
        $dto = new PaymentCreationResultDto(
            transactionId: 'txn_123',
            status: PaymentStatus::AUTHORIZED,
            payload: JsonPayload::fromArray(['provider' => 'fake']),
        );

        $this->assertSame('authorized', $dto->toArray()['status']);
    }

    public function test_shipment_creation_result_to_array_exposes_scalar_status(): void
    {
        $dto = new ShipmentCreationResultDto(
            trackingNumber: 'trk_123',
            status: ShipmentStatus::SHIPPED,
            cost: 9.99,
            payload: JsonPayload::fromArray(['provider' => 'fake']),
        );

        $this->assertSame('shipped', $dto->toArray()['status']);
    }
}
