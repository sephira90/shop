<?php

declare(strict_types=1);

namespace App\Application\Account\Orders\Queries;

use App\Application\Account\Orders\Contracts\AccountOrderReadRepository;
use App\Application\Account\Orders\Dto\AccountOrderPaginatedResultDto;

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
