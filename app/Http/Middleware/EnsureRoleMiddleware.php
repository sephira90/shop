<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Api\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleMiddleware
{
    /**
     * Handle incoming request and verify user role.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error('Authentication is required.', Response::HTTP_UNAUTHORIZED);
        }

        if ($roles === [] || $user->roles()->whereIn('name', $roles)->exists()) {
            return $next($request);
        }

        return ApiResponse::error('Access denied for current role.', Response::HTTP_FORBIDDEN);
    }
}
