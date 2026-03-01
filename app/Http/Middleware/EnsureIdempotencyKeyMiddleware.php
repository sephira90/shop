<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Api\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureIdempotencyKeyMiddleware
{
    /**
     * Require a non-empty Idempotency-Key header before the transport layer proceeds.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));

        if ($idempotencyKey === '') {
            return ApiResponse::error('Idempotency-Key header is required.', Response::HTTP_BAD_REQUEST);
        }

        $request->headers->set('Idempotency-Key', $idempotencyKey);

        $response = $next($request);

        return $response;
    }
}
