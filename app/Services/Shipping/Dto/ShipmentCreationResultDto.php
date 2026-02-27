<?php

declare(strict_types=1);

namespace App\Services\Shipping\Dto;

use App\Enums\ShipmentStatus;
use App\Support\Data\JsonPayload;

final readonly class ShipmentCreationResultDto
{
    public function __construct(
        public string $trackingNumber,
        public ShipmentStatus $status,
        public float $cost,
        public JsonPayload $payload,
    ) {}

    /**
     * Convert DTO to transport-friendly payload.
     *
     * @return array{tracking_number:string,status:string,cost:float,payload:array<string,mixed>}
     */
    public function toArray(): array
    {
        return [
            'tracking_number' => $this->trackingNumber,
            'status' => $this->status->value,
            'cost' => $this->cost,
            'payload' => $this->payload->toArray(),
        ];
    }
}
