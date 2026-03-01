<?php

declare(strict_types=1);

namespace App\Application\Admin\Orders\Queries;

use App\Application\Admin\Orders\Dto\AdminOrderPaginatedResultDto;
use App\Repositories\AdminOrderReadRepository;

final class PaginateAdminOrdersHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly AdminOrderReadRepository $adminOrderReadRepository,
    ) {}

    /**
     * Execute admin orders list query.
     */
    public function handle(PaginateAdminOrdersQuery $query): AdminOrderPaginatedResultDto
    {
        $paginator = $this->adminOrderReadRepository->paginateSummaryForAdmin($query->filter);

        return AdminOrderPaginatedResultDto::fromPaginator($paginator);
    }
}
