<?php

declare(strict_types=1);

namespace App\Application\Account\Orders\Dto;

use App\Enums\OrderStatus;
use App\Support\Data\TypedValue;

final readonly class AccountOrderListFilterDto
{
    public function __construct(
        public int $page,
        public int $perPage,
        public ?string $search,
        public ?OrderStatus $orderStatus,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        $search = isset($validated['q']) ? TypedValue::trimmedString($validated['q']) : '';
        $search = $search === '' ? null : $search;

        $orderStatus = null;
        if (isset($validated['status']) && $validated['status'] !== '') {
            $orderStatus = OrderStatus::tryFrom(TypedValue::string($validated['status']));
        }

        return new self(
            page: max(1, TypedValue::int($validated['page'] ?? 1)),
            perPage: min(200, max(1, TypedValue::int($validated['per_page'] ?? 20))),
            search: $search,
            orderStatus: $orderStatus,
        );
    }
}
