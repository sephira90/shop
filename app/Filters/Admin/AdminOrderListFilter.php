<?php

declare(strict_types=1);

namespace App\Filters\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;

final readonly class AdminOrderListFilter
{
    /**
     * Create filter object.
     */
    public function __construct(
        public int $page,
        public int $perPage,
        public ?string $search,
        public ?OrderStatus $orderStatus,
        public ?PaymentStatus $paymentStatus,
        public ?ShipmentStatus $shipmentStatus,
    ) {}

    /**
     * Build filter from validated query payload.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: max(1, (int) ($validated['per_page'] ?? 30)),
            search: self::normalizeSearch($validated['q'] ?? null),
            orderStatus: isset($validated['status']) ? OrderStatus::tryFrom((string) $validated['status']) : null,
            paymentStatus: isset($validated['payment_status']) ? PaymentStatus::tryFrom((string) $validated['payment_status']) : null,
            shipmentStatus: isset($validated['shipment_status']) ? ShipmentStatus::tryFrom((string) $validated['shipment_status']) : null,
        );
    }

    /**
     * Normalize search payload into nullable non-empty string.
     */
    private static function normalizeSearch(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $search = trim($value);

        return $search !== '' ? $search : null;
    }
}
