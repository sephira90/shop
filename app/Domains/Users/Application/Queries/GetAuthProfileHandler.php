<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Queries;

use App\Domains\Users\Application\Dto\AuthUserDto;
use App\Domains\Users\Support\AuthUserDtoMapper;

final class GetAuthProfileHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly AuthUserDtoMapper $authUserDtoMapper,
    ) {}

    /**
     * Execute auth me profile query.
     */
    public function handle(GetAuthProfileQuery $query): AuthUserDto
    {
        return $this->authUserDtoMapper->map($query->user);
    }
}
