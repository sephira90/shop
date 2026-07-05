<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Queries;

use App\Domains\Users\Application\Dto\AccountOrderLegacyPaginatedResultDto;
use App\Domains\Users\Contracts\AccountOrderReadRepository;

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
