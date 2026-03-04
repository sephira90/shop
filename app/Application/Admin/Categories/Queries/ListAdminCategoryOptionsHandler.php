<?php

declare(strict_types=1);

namespace App\Application\Admin\Categories\Queries;

use App\Application\Admin\Categories\Contracts\AdminCategoryReadRepository;
use App\Application\Admin\Categories\Dto\AdminCategoryOptionsResultDto;

final class ListAdminCategoryOptionsHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly AdminCategoryReadRepository $categoryRepository,
    ) {}

    /**
     * Execute admin category selector query.
     */
    public function handle(ListAdminCategoryOptionsQuery $query): AdminCategoryOptionsResultDto
    {
        $categories = $this->categoryRepository->listOptionsForAdmin($query->filter);

        return AdminCategoryOptionsResultDto::fromCategories($categories);
    }
}
