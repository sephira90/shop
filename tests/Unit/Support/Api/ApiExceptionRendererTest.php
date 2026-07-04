<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Api;

use App\Domain\Exceptions\OrderStaleAggregateException;
use App\Domain\Exceptions\OrderTransitionException;
use App\Services\Webhook\WebhookIngressErrorCode;
use App\Services\Webhook\WebhookIngressException;
use App\Support\Api\ApiExceptionRenderer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

final class ApiExceptionRendererTest extends TestCase
{
    private ApiExceptionRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = $this->app->make(ApiExceptionRenderer::class);
    }

    #[Test]
    public function returns_null_for_non_api_requests_so_laravel_handles_them(): void
    {
        $response = ($this->renderer)(new \RuntimeException('boom'), Request::create('/web/foo'));

        $this->assertNull($response);
    }

    #[Test]
    public function validation_exception_maps_to_422_validation_failed_with_validation_details(): void
    {
        $exception = ValidationException::withMessages(['email' => ['The email field is required.']]);

        $response = $this->renderForApi($exception);
        $payload = $this->decodedErrorEnvelope($response);

        $this->assertSame(422, $response->status());
        $this->assertSame('ValidationException', $payload['type']);
        $this->assertSame('validation_failed', $payload['code']);
        $this->assertArrayHasKey('validation', $payload);

        $validation = $payload['validation'] ?? null;
        $this->assertIsArray($validation);
        $this->assertArrayHasKey('email', $validation);
    }

    #[Test]
    public function authentication_exception_maps_to_401_unauthenticated(): void
    {
        $payload = $this->decodedErrorEnvelope(
            $this->renderForApi(new AuthenticationException('Unauthenticated.'))
        );

        $this->assertSame('AuthenticationException', $payload['type']);
        $this->assertSame('unauthenticated', $payload['code']);
        $this->assertSame('Unauthenticated.', $payload['message']);
        $this->assertArrayNotHasKey('validation', $payload);
    }

    #[Test]
    public function authorization_exception_maps_to_403_forbidden(): void
    {
        $response = $this->renderForApi(new AuthorizationException());
        $payload = $this->decodedErrorEnvelope($response);

        $this->assertSame(403, $response->status());
        $this->assertSame('AuthorizationException', $payload['type']);
        $this->assertSame('forbidden', $payload['code']);
    }

    #[Test]
    public function not_found_http_exception_maps_to_404_not_found(): void
    {
        $response = $this->renderForApi(new NotFoundHttpException('Product not found.'));
        $payload = $this->decodedErrorEnvelope($response);

        $this->assertSame(404, $response->status());
        $this->assertSame('NotFoundHttpException', $payload['type']);
        $this->assertSame('not_found', $payload['code']);
        $this->assertSame('Product not found.', $payload['message']);
    }

    #[Test]
    public function bad_request_http_exception_maps_to_400_domain_failure(): void
    {
        $response = $this->renderForApi(new BadRequestHttpException('Idempotency-Key header is required.'));
        $payload = $this->decodedErrorEnvelope($response);

        $this->assertSame(400, $response->status());
        $this->assertSame('BadRequestHttpException', $payload['type']);
        $this->assertSame('domain_failure', $payload['code']);
    }

    #[Test]
    public function other_http_status_codes_collapse_to_internal_error_literal(): void
    {
        $response = $this->renderForApi(new ConflictHttpException('Conflict.'));
        $payload = $this->decodedErrorEnvelope($response);

        $this->assertSame(409, $response->status());
        $this->assertSame('internal_error', $payload['code']);
    }

    #[Test]
    public function order_stale_aggregate_exception_maps_to_409_conflict_with_stale_aggregate_code(): void
    {
        $response = $this->renderForApi(OrderStaleAggregateException::forPaymentInitiation());
        $payload = $this->decodedErrorEnvelope($response);

        $this->assertSame(409, $response->status());
        $this->assertSame('OrderStaleAggregateException', $payload['type']);
        $this->assertSame('stale_aggregate', $payload['code']);
    }

    #[Test]
    public function order_transition_exception_maps_to_state_transition_not_allowed(): void
    {
        $response = $this->renderForApi(OrderTransitionException::paymentStatusTransitionNotAllowed());
        $payload = $this->decodedErrorEnvelope($response);

        $this->assertSame(422, $response->status());
        $this->assertSame('state_transition_not_allowed', $payload['code']);
    }

    #[Test]
    public function webhook_ingress_exception_maps_to_its_declared_status_and_webhook_ingress_rejected_code(): void
    {
        $response = $this->renderForApi(WebhookIngressException::invalidSignature('Signature mismatch.'));
        $payload = $this->decodedErrorEnvelope($response);

        $this->assertSame(422, $response->status());
        $this->assertSame('WebhookIngressException', $payload['type']);
        $this->assertSame('webhook_ingress_rejected', $payload['code']);
        $this->assertSame('Signature mismatch.', $payload['message']);
    }

    #[Test]
    public function bare_domain_exception_falls_back_to_422_domain_failure(): void
    {
        $response = $this->renderForApi(new \DomainException('Some generic domain failure.'));
        $payload = $this->decodedErrorEnvelope($response);

        $this->assertSame(422, $response->status());
        $this->assertSame('DomainException', $payload['type']);
        $this->assertSame('domain_failure', $payload['code']);
    }

    #[Test]
    public function five_xx_messages_are_masked_to_internal_server_error_without_leaking_internals(): void
    {
        $response = $this->renderForApi(new \RuntimeException('Sensitive database connection details.'));

        $this->assertSame(500, $response->status());
        $payload = $this->decodedErrorEnvelope($response);
        $this->assertSame('internal_error', $payload['code']);
        $this->assertSame('Internal server error.', $payload['message']);
        $this->assertStringNotContainsString('Sensitive database connection details.', $response->getContent() ?: '');
    }

    #[Test]
    public function empty_message_falls_back_to_status_text(): void
    {
        $payload = $this->decodedErrorEnvelope($this->renderForApi(new AuthorizationException('')));

        $this->assertSame('Forbidden', $payload['message']);
    }

    #[Test]
    public function correlation_id_header_is_mirrored_into_request_id_envelope_field(): void
    {
        $request = Request::create('/api/v1/example');
        $request->headers->set('X-Correlation-Id', 'r1-renderer-parity-cid');
        $this->app->instance('request', $request);

        $payload = $this->decodedErrorEnvelope($this->renderForApi(new AuthenticationException(), $request));

        $this->assertSame('r1-renderer-parity-cid', $payload['request_id']);
    }

    #[Test]
    public function known_and_unknown_login_paths_emit_byte_identical_envelope(): void
    {
        $first = $this->renderForApi(
            ValidationException::withMessages(['password' => ['Invalid credentials.']]),
        )->getContent();

        $second = $this->renderForApi(
            ValidationException::withMessages(['password' => ['Invalid credentials.']]),
        )->getContent();

        $this->assertSame($first, $second);

        $payload = json_decode((string) $first, true);
        $this->assertIsArray($payload);
        $error = $payload['error'] ?? null;
        $this->assertIsArray($error);
        $this->assertSame('validation_failed', $error['code']);
    }

    #[Test]
    public function error_envelope_always_carries_type_and_code_together(): void
    {
        $payload = $this->decodedErrorEnvelope(
            $this->renderForApi(WebhookIngressException::missingEventId())
        );

        $this->assertArrayHasKey('type', $payload);
        $this->assertArrayHasKey('code', $payload);
        $this->assertSame(WebhookIngressErrorCode::MISSING_EVENT_ID->value, 'missing_event_id');
        $this->assertSame('webhook_ingress_rejected', $payload['code']);
    }

    /**
     * Invoke the renderer against an /api/* request and assert the result is non-null.
     *
     * The renderer returns null only for non-API paths; in tests we always target
     * /api/* so we narrow the type for callers without a nullable-widering dance.
     */
    private function renderForApi(\Throwable $exception, ?Request $request = null): JsonResponse
    {
        $request ??= Request::create('/api/v1/example');
        $response = ($this->renderer)($exception, $request);
        $this->assertInstanceOf(JsonResponse::class, $response, 'Renderer must produce a JsonResponse for /api/* paths.');

        return $response;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function decodedErrorEnvelope(JsonResponse $response): array
    {
        $payload = $response->getData(true);
        $this->assertIsArray($payload, 'Renderer must return a JSON object body.');

        $error = $payload['error'] ?? null;
        $this->assertIsArray($error, 'Renderer must return a structured error envelope.');

        return $error;
    }
}
