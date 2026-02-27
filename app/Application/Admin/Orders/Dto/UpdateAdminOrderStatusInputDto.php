<?php

declare(strict_types=1);

namespace App\Application\Admin\Orders\Dto;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;

final readonly class UpdateAdminOrderStatusInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            status: self::normalizeOrderStatus($validated['status'] ?? null),
            paymentStatus: self::normalizePaymentStatus($validated['payment_status'] ?? null),
            shipmentStatus: self::normalizeShipmentStatus($validated['shipment_status'] ?? null),
        );
    }

    public function __construct(
        public ?OrderStatus $status,
        public ?PaymentStatus $paymentStatus,
        public ?ShipmentStatus $shipmentStatus,
    ) {}

    /**
     * Normalize nullable order status input.
     */
    private static function normalizeOrderStatus(mixed $value): ?OrderStatus
    {
        return self::normalizeEnum($value, OrderStatus::class);
    }

    /**
     * Normalize nullable payment status input.
     */
    private static function normalizePaymentStatus(mixed $value): ?PaymentStatus
    {
        return self::normalizeEnum($value, PaymentStatus::class);
    }

    /**
     * Normalize nullable shipment status input.
     */
    private static function normalizeShipmentStatus(mixed $value): ?ShipmentStatus
    {
        return self::normalizeEnum($value, ShipmentStatus::class);
    }

    /**
     * @template TEnum of \BackedEnum
     *
     * @param  class-string<TEnum>  $enumClass
     * @return TEnum|null
     */
    private static function normalizeEnum(mixed $value, string $enumClass): ?\BackedEnum
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        /** @var TEnum|null $enumValue */
        $enumValue = $enumClass::tryFrom($normalized);

        return $enumValue;
    }
}
