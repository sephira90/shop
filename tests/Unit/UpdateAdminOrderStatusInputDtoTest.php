<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Admin\Orders\Dto\UpdateAdminOrderStatusInputDto;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use PHPUnit\Framework\TestCase;

final class UpdateAdminOrderStatusInputDtoTest extends TestCase
{
    public function test_from_validated_maps_status_fields_to_enums(): void
    {
        $dto = UpdateAdminOrderStatusInputDto::fromValidated([
            'status' => 'cancelled',
            'payment_status' => 'captured',
            'shipment_status' => 'delivered',
        ]);

        $this->assertSame(OrderStatus::CANCELLED, $dto->status);
        $this->assertSame(PaymentStatus::CAPTURED, $dto->paymentStatus);
        $this->assertSame(ShipmentStatus::DELIVERED, $dto->shipmentStatus);
    }

    public function test_from_validated_normalizes_invalid_or_empty_values_to_null(): void
    {
        $dto = UpdateAdminOrderStatusInputDto::fromValidated([
            'status' => '',
            'payment_status' => null,
            'shipment_status' => 123,
        ]);

        $this->assertNull($dto->status);
        $this->assertNull($dto->paymentStatus);
        $this->assertNull($dto->shipmentStatus);
    }
}
