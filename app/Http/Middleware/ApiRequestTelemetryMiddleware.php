<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Observability\ObservabilityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiRequestTelemetryMiddleware
{
    /**
     * Create middleware instance.
     */
    public function __construct(private readonly ObservabilityService $observabilityService) {}

    /**
     * Capture API request latency metrics.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);

        /** @var Response $response */
        $response = $next($request);

        if ($request->is('api/*')) {
            $durationMs = (hrtime(true) - $startedAt) / 1_000_000;

            $this->observabilityService->apiRequest(
                method: (string) $request->method(),
                path: '/'.ltrim((string) $request->path(), '/'),
                status: $response->getStatusCode(),
                durationMs: $durationMs,
            );
        }

        return $response;
    }
}
