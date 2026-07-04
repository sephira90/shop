<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Assert the R1 additive error contract (type+code, message masking,
 * stable literals) is emitted end-to-end through the HTTP layer for
 * each renderer-mapped category reachable via a real route.
 */
final class ApiErrorContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class]);
    }

    public function test_validation_failure_carries_additive_error_code(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonPath('error.type', 'ValidationException')
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['message', 'type', 'code', 'validation']]);
    }

    public function test_unauthenticated_request_returns_unauthenticated_code(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.type', 'AuthenticationException')
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_resource_not_found_returns_not_found_code(): void
    {
        $this->getJson('/api/v1/catalog/products/definitely-missing-slug')
            ->assertNotFound()
            ->assertJsonPath('error.type', 'NotFoundHttpException')
            ->assertJsonPath('error.code', 'not_found')
            ->assertJsonPath('error.message', 'Product not found.');
    }

    public function test_missing_required_header_returns_domain_failure_code_with_400(): void
    {
        $this->postJson('/api/v1/checkout/place-order', [])
            ->assertBadRequest()
            ->assertJsonPath('error.type', 'BadRequestHttpException')
            ->assertJsonPath('error.code', 'domain_failure')
            ->assertJsonPath('error.message', 'Idempotency-Key header is required.');
    }

    public function test_server_error_envelope_is_masked_and_marks_internal_error_code(): void
    {
        $renderer = $this->app->make(\App\Support\Api\ApiExceptionRenderer::class);
        $request = \Illuminate\Http\Request::create('/api/v1/internal-error-probe');
        $response = $renderer(new \RuntimeException('internal details'), $request);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertSame(500, $response->status());
        $payload = $response->getData(true);
        $this->assertIsArray($payload);
        $error = $payload['error'] ?? null;
        $this->assertIsArray($error);
        $this->assertSame('internal_error', $error['code']);
        $this->assertSame('Internal server error.', $error['message']);
        $this->assertStringNotContainsString('internal details', (string) $response->getContent());
    }

    public function test_request_id_is_mirrored_when_correlation_header_present(): void
    {
        $this->withHeader('X-Correlation-Id', 'r1-contract-cid')
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.request_id', 'r1-contract-cid');
    }
}
