<?php

declare(strict_types=1);

namespace App\Application\Admin\Promotions\Queries;

use App\Models\Promotion;
use App\Repositories\PromotionRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PaginateAdminPromotionsHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly PromotionRepository $promotionRepository,
    ) {}

    /**
     * Execute admin promotions list query.
     *
     * @return LengthAwarePaginator<int, Promotion>
     */
    public function handle(PaginateAdminPromotionsQuery $query): LengthAwarePaginator
    {
        return $this->promotionRepository->paginateForAdmin($query->filter);
    }
}
