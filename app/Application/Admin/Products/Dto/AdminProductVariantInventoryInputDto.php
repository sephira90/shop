<?php

declare(strict_types=1);

namespace App\Application\Admin\Products\Dto;

final readonly class AdminProductVariantInventoryInputDto
{
    public static function fromValidated(mixed $validated): self
    {
        $payload = is_array($validated) ? $validated : [];

        $quantity = self::normalizeInteger($payload['quantity'] ?? 0, 0);
        $reservedQuantity = self::normalizeInteger($payload['reserved_quantity'] ?? 0, 0);
        $lowStockThreshold = self::normalizeInteger($payload['low_stock_threshold'] ?? 3, 3);

        if ($reservedQuantity > $quantity) {
            $reservedQuantity = $quantity;
        }

        return new self(
            quantity: max(0, $quantity),
            reservedQuantity: max(0, $reservedQuantity),
            lowStockThreshold: max(0, $lowStockThreshold),
        );
    }

    public function __construct(
        public int $quantity,
        public int $reservedQuantity,
        public int $lowStockThreshold,
    ) {}

    /**
     * @return array{quantity: int, reserved_quantity: int, low_stock_threshold: int}
     */
    public function toPersistenceAttributes(): array
    {
        return [
            'quantity' => $this->quantity,
            'reserved_quantity' => $this->reservedQuantity,
            'low_stock_threshold' => $this->lowStockThreshold,
        ];
    }

    private static function normalizeInteger(mixed $value, int $fallback): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $fallback;
    }
}
