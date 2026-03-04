<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Queries;

use App\Application\Admin\Promotions\Contracts\AdminPromotionReadRepository;
use App\Application\Admin\Promotions\Dto\AdminPromotionPaginatedResultDto;

final class PaginateAdminPromotionsHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly AdminPromotionReadRepository $promotionRepository,
    ) {}

    /**
     * Execute admin promotions list query.
     */
    public function handle(PaginateAdminPromotionsQuery $query): AdminPromotionPaginatedResultDto
    {
        $paginator = $this->promotionRepository->paginateForAdmin($query->filter);

        return AdminPromotionPaginatedResultDto::fromPaginator($paginator);
    }
}
