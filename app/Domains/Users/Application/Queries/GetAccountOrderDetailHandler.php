<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Queries;

use App\Domains\Users\Application\Dto\AccountOrderDetailResultDto;
use App\Domains\Users\Contracts\AccountOrderReadRepository;

final class GetAccountOrderDetailHandler
{
    public function __construct(
        private readonly AccountOrderReadRepository $accountOrderReadRepository,
    ) {}

    public function handle(GetAccountOrderDetailQuery $query): ?AccountOrderDetailResultDto
    {
        $order = $this->accountOrderReadRepository->findDetailForUser($query->user, $query->orderId);

        return $order === null ? null : AccountOrderDetailResultDto::fromOrder($order);
    }
}
