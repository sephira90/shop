<?php

declare(strict_types=1);

namespace App\Application\Account\Orders\Dto;

use Illuminate\Database\Eloquent\Model;

final readonly class AccountOrderSummaryStatusGroupDto
{
    public function __construct(
        public ?string $orderStatus,
        public ?string $paymentStatus,
        public ?string $shipmentStatus,
        public int $count,
    ) {}

    public static function fromModel(Model $model): self
    {
        return new self(
            orderStatus: self::normalizeNullableString($model->getRawOriginal('status')),
            paymentStatus: self::normalizeNullableString($model->getRawOriginal('payment_status')),
            shipmentStatus: self::normalizeNullableString($model->getRawOriginal('shipment_status')),
            count: self::normalizeCount($model->getAttribute('aggregate_count')),
        );
    }

    private static function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }

    private static function normalizeCount(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
