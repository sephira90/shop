<?php

declare(strict_types=1);

namespace App\Support\Smoke\Api;

use App\Support\Data\TypedValue;
use DomainException;

final class ApiSmokeAssertions
{
    /**
     * Assert HTTP status code.
     */
    public function assertStatus(int $actual, int $expected, string $scope): void
    {
        if ($actual !== $expected) {
            throw new DomainException(sprintf('%s expected status %d, got %d.', $scope, $expected, $actual));
        }
    }

    /**
     * Assert required keys exist in payload.
     *
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $keys
     */
    public function assertHasKeys(array $payload, array $keys, string $scope): void
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $payload)) {
                throw new DomainException(sprintf('%s missing required key "%s".', $scope, $key));
            }
        }
    }

    /**
     * Assert standard error envelope shape.
     *
     * @param  array<string, mixed>  $payload
     */
    public function assertErrorEnvelope(array $payload, string $scope): void
    {
        $this->assertHasKeys($payload, ['error'], $scope);

        if (! is_array($payload['error'])) {
            throw new DomainException(sprintf('%s error payload is not an object.', $scope));
        }

        $errorPayload = TypedValue::associativeArray($payload['error']);

        if (! array_key_exists('message', $errorPayload)) {
            throw new DomainException(sprintf('%s error payload missing "message".', $scope));
        }
    }

    /**
     * Assert paginated meta payload shape.
     */
    public function assertMetaShape(mixed $meta, string $scope): void
    {
        if (! is_array($meta)) {
            throw new DomainException(sprintf('%s meta payload is not an object.', $scope));
        }

        $this->assertHasKeys(TypedValue::associativeArray($meta), ['current_page', 'last_page', 'per_page', 'total'], $scope.' meta');
    }
}
