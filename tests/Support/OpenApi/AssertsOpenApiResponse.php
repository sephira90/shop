<?php

declare(strict_types=1);

namespace Tests\Support\OpenApi;

use Illuminate\Http\JsonResponse;
use Illuminate\Testing\TestResponse;

/**
 * Convenience trait for feature tests that need to assert OpenAPI conformance.
 *
 * Wraps {@see SpecAssertionHelper::assertResponseMatches()} behind a short
 * helper so test bodies stay readable:
 *
 *     $response = $this->postJson('/api/v1/auth/login', $payload);
 *     $this->assertResponseMatchesOpenApiSpec($response, 'POST', '/auth/login');
 */
trait AssertsOpenApiResponse
{
    /**
     * @param  TestResponse<JsonResponse>  $response
     */
    protected function assertResponseMatchesOpenApiSpec(TestResponse $response, string $method, string $path): void
    {
        SpecAssertionHelper::assertResponseMatches($response, $method, $path);
    }
}
