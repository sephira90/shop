<?php

declare(strict_types=1);

namespace App\Application\Account\Orders\Dto;

use App\Models\Shipment;

final readonly class AccountOrderShipmentResultDto
{
    public static function fromShipment(Shipment $shipment): self
    {
        return new self(
            provider: (string) $shipment->provider,
            trackingNumber: (string) $shipment->tracking_number,
            status: self::nullableStatus($shipment->getRawOriginal('status')),
            cost: (float) $shipment->cost,
        );
    }

    public function __construct(
        public string $provider,
        public string $trackingNumber,
        public ?string $status,
        public float $cost,
    ) {}

    /**
     * @return array{provider:string,tracking_number:string,status:string|null,cost:float}
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'tracking_number' => $this->trackingNumber,
            'status' => $this->status,
            'cost' => $this->cost,
        ];
    }

    private static function nullableStatus(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
