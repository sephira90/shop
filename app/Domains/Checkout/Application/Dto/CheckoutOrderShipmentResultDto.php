<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Application\Dto;

use App\Models\Shipment;

final readonly class CheckoutOrderShipmentResultDto
{
    public static function fromShipment(Shipment $shipment): self
    {
        $status = $shipment->getRawOriginal('status');

        return new self(
            provider: (string) $shipment->provider,
            trackingNumber: (string) $shipment->tracking_number,
            status: is_string($status) && trim($status) !== '' ? $status : null,
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
     * @return array{
     *     provider:string,
     *     tracking_number:string,
     *     status:string|null,
     *     cost:float
     * }
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
}
