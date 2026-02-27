<?php

declare(strict_types=1);

namespace App\Application\Auth\Queries;

use App\Application\Auth\Dto\AuthUserDto;
use App\Application\Auth\Support\AuthUserDtoMapper;

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
