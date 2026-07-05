<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Queries;

use App\Domains\Users\Application\Dto\AccountOrdersSummaryResultDto;
use App\Domains\Users\Contracts\AccountOrderReadRepository;
use App\Domains\Users\Support\AccountOrderSummaryProjector;

final class GetAccountOrdersSummaryHandler
{
    public function __construct(
        private readonly AccountOrderReadRepository $accountOrderReadRepository,
        private readonly AccountOrderSummaryProjector $accountOrderSummaryProjector,
    ) {}

    public function handle(GetAccountOrdersSummaryQuery $query): AccountOrdersSummaryResultDto
    {
        return $this->accountOrderSummaryProjector->project(
            $this->accountOrderReadRepository->getSummaryAggregateForUser($query->user)
        );
    }
}
