<?php

declare(strict_types=1);

namespace App\Application\Account\Orders\Queries;

use App\Application\Account\Orders\Contracts\AccountOrderReadRepository;
use App\Application\Account\Orders\Dto\AccountOrderLegacyPaginatedResultDto;

final class PaginateLegacyAccountOrdersHandler
{
    public function __construct(
        private readonly AccountOrderReadRepository $accountOrderReadRepository,
    ) {}

    public function handle(PaginateLegacyAccountOrdersQuery $query): AccountOrderLegacyPaginatedResultDto
    {
        return AccountOrderLegacyPaginatedResultDto::fromPaginator(
            $this->accountOrderReadRepository->paginateLegacyDetailsForUser($query->user, $query->filter)
        );
    }
}
