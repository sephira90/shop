<?php

declare(strict_types=1);

namespace App\Support\Smoke\Api;

final readonly class ApiSmokeResponse
{
    /**
     * Create API smoke response envelope.
     *
     * @param  array<string, mixed>  $json
     */
    public function __construct(
        public int $status,
        public array $json,
    ) {}
}
