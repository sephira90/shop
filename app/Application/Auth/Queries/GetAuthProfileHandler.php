<?php

declare(strict_types=1);

namespace App\Application\Auth\Queries;

use App\Application\Auth\Support\AuthUserPayloadBuilder;

final class GetAuthProfileHandler
{
    /**
     * Create query handler instance.
     */
    public function __construct(
        private readonly AuthUserPayloadBuilder $authUserPayloadBuilder,
    ) {}

    /**
     * Execute auth me profile query.
     *
     * @return array<string, mixed>
     */
    public function handle(GetAuthProfileQuery $query): array
    {
        return $this->authUserPayloadBuilder->build($query->user);
    }
}
