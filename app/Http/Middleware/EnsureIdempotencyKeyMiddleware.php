<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
            throw new BadRequestHttpException('Idempotency-Key header is required.');
        }

        $request->headers->set('Idempotency-Key', $idempotencyKey);

        return $next($request);
    }
}
