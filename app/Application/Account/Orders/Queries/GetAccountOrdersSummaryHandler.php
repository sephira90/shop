<?php

declare(strict_types=1);

namespace App\Application\Account\Orders\Queries;

use App\Application\Account\Orders\Contracts\AccountOrderReadRepository;
use App\Application\Account\Orders\Dto\AccountOrdersSummaryResultDto;
use App\Application\Account\Orders\Support\AccountOrderSummaryProjector;

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
