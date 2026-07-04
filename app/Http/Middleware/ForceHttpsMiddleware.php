<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ForceHttpsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        assert($response instanceof Response);

        if (! $this->shouldEnforceHttps()) {
            return $response;
        }

        if ($request->isSecure()) {
            return $response;
        }

        if ($this->forwardedProtoIsHttps($request)) {
            return $response;
        }

        return redirect()->secure($request->getRequestUri(), 301);
    }

    private function shouldEnforceHttps(): bool
    {
        if (app()->environment('local')) {
            return false;
        }

        return (bool) config('security.force_https', true);
    }

    private function forwardedProtoIsHttps(Request $request): bool
    {
        $header = $request->headers->get('X-Forwarded-Proto');

        if (! is_string($header) || $header === '') {
            return false;
        }

        $segments = explode(',', $header);

        foreach ($segments as $segment) {
            if (strtolower(trim($segment)) === 'https') {
                return true;
            }
        }

        return false;
    }
}
