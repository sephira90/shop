<?php

declare(strict_types=1);

namespace App\Filters\Account;

use App\Enums\OrderStatus;

final readonly class AccountOrderListFilter
{
    /**
     * Create filter instance.
     */
    public function __construct(
        public int $page,
        public int $perPage,
        public ?string $search,
        public ?OrderStatus $orderStatus,
    ) {}

    /**
     * Build filter from validated payload.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        $search = isset($validated['q']) ? trim((string) $validated['q']) : '';
        $search = $search === '' ? null : $search;

        $orderStatus = null;
        if (isset($validated['status']) && $validated['status'] !== '') {
            $rawStatus = (string) $validated['status'];
            $orderStatus = OrderStatus::tryFrom($rawStatus);
        }

        return new self(
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(200, max(1, (int) ($validated['per_page'] ?? 20))),
            search: $search,
            orderStatus: $orderStatus,
        );
    }
}
