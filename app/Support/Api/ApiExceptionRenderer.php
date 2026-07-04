<?php

declare(strict_types=1);

namespace App\Support\Api;

use App\Domain\Exceptions\OrderStaleAggregateException;
use App\Domain\Exceptions\OrderTransitionException;
use App\Services\Webhook\WebhookIngressException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Centralized API exception renderer for the /api/* surface.
 *
 * Registered as the single Laravel exception render boundary via
 * bootstrap/app.php. Behavior contract:
 *
 * - Status mapping preserves the historical closure verbatim
 *   (ValidationException 422, AuthenticationException 401,
 *    AuthorizationException 403, bare DomainException 422,
 *    HttpExceptionInterface its own status, default 500),
 *   except that OrderStaleAggregateException is promoted to 409 Conflict
 *   because the aggregate became stale under concurrent update, which is
 *   the semantically correct HTTP status for that failure mode.
 *
 * - 5xx messages are always masked to "Internal server error." so domain
 *   internals are never leaked to clients.
 *
 * - The envelope always carries `error.type` (PHP class basename) for
 *   backward compatibility. The additive `error.code` is a stable literal
 *   from the closed ApiErrorCode enum; clients should pin on it for
 *   machine handling. See ApiErrorCode for the migration note.
 */
final class ApiExceptionRenderer
{
    /**
     * Render an exception into the API error envelope, or return null
     * to let Laravel handle non-API requests with its default behavior.
     */
    public function __invoke(Throwable $exception, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        $code = $this->resolveCode($exception);
        $status = $this->resolveStatus($exception, $code);

        $message = $status >= 500 ? 'Internal server error.' : trim($exception->getMessage());
        if ($message === '') {
            $message = Response::$statusTexts[$status] ?? 'Request failed.';
        }

        $error = [
            'type' => class_basename($exception),
            'code' => $code->value,
        ];

        if ($exception instanceof ValidationException) {
            $error['validation'] = $exception->errors();
        }

        return ApiResponse::error($message, $status, $error);
    }

    /**
     * Map an exception to its stable ApiErrorCode literal.
     *
     * Order matters: more specific subclasses must be tested before their
     * DomainException base. Stale-aggregate and webhook-ingress types get
     * dedicated codes; other OrderTransitionException subclasses stay on
     * the generic state-transition code; any other DomainException falls
     * through to domain_failure.
     */
    private function resolveCode(Throwable $exception): ApiErrorCode
    {
        return match (true) {
            $exception instanceof ValidationException => ApiErrorCode::ValidationFailed,
            $exception instanceof AuthenticationException => ApiErrorCode::Unauthenticated,
            $exception instanceof AuthorizationException => ApiErrorCode::Forbidden,
            $exception instanceof OrderStaleAggregateException => ApiErrorCode::StaleAggregate,
            $exception instanceof WebhookIngressException => ApiErrorCode::WebhookIngressRejected,
            $exception instanceof OrderTransitionException => ApiErrorCode::StateTransitionNotAllowed,
            $exception instanceof \DomainException => ApiErrorCode::DomainFailure,
            $exception instanceof HttpExceptionInterface => $this->resolveHttpCode($exception),
            default => ApiErrorCode::InternalError,
        };
    }

    /**
     * Resolve the HTTP status code from the exception and its resolved code.
     *
     * Stale-aggregate is promoted to 409 Conflict here, before the generic
     * DomainException 422 fallback. HttpExceptionInterface keeps its own
     * status. All other cases follow the historical closure mapping.
     */
    private function resolveStatus(Throwable $exception, ApiErrorCode $code): int
    {
        return match (true) {
            $exception instanceof ValidationException => 422,
            $exception instanceof AuthenticationException => 401,
            $exception instanceof AuthorizationException => 403,
            $exception instanceof OrderStaleAggregateException => 409,
            $exception instanceof WebhookIngressException => $exception->statusCode(),
            $exception instanceof \DomainException => 422,
            $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
            default => 500,
        };
    }

    /**
     * Map an HttpExceptionInterface status to its ApiErrorCode.
     *
     * Only the categories that actually surface in app/ today are mapped
     * to dedicated codes; everything else collapses to InternalError so
     * the closed literal set stays stable and intentionally small.
     */
    private function resolveHttpCode(HttpExceptionInterface $exception): ApiErrorCode
    {
        return match ($exception->getStatusCode()) {
            400 => ApiErrorCode::DomainFailure,
            404 => ApiErrorCode::NotFound,
            default => ApiErrorCode::InternalError,
        };
    }
}
