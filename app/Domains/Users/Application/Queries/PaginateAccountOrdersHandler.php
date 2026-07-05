<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Queries;

use App\Domains\Users\Application\Dto\AccountOrderPaginatedResultDto;
use App\Domains\Users\Contracts\AccountOrderReadRepository;

final class PaginateAccountOrdersHandler
{
    public function __construct(
        private readonly AccountOrderReadRepository $accountOrderReadRepository,
    ) {}

    public function handle(PaginateAccountOrdersQuery $query): AccountOrderPaginatedResultDto
    {
        return AccountOrderPaginatedResultDto::fromPaginator(
            $this->accountOrderReadRepository->paginateSummariesForUser($query->user, $query->filter)
        );
    }
}
