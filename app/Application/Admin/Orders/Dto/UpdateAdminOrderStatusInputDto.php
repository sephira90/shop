<?php

declare(strict_types=1);

namespace App\Application\Admin\Orders\Dto;

final readonly class UpdateAdminOrderStatusInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            status: self::normalizeNullableString($validated['status'] ?? null),
            paymentStatus: self::normalizeNullableString($validated['payment_status'] ?? null),
            shipmentStatus: self::normalizeNullableString($validated['shipment_status'] ?? null),
        );
    }

    public function __construct(
        public ?string $status,
        public ?string $paymentStatus,
        public ?string $shipmentStatus,
    ) {}

    /**
     * Normalize nullable string input.
     */
    private static function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }
}
