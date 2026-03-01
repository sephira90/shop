<?php

declare(strict_types=1);

namespace App\Application\Account\Orders\Queries;

use App\Application\Account\Orders\Contracts\AccountOrderReadRepository;
use App\Application\Account\Orders\Dto\AccountOrderDetailResultDto;

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
