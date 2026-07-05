<?php

declare(strict_types=1);

namespace App\Domains\Users\Middleware;

use App\Domains\Users\Application\AuthActiveUserRevalidator;
use App\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureActiveApiUser
{
    public function __construct(
        private AuthActiveUserRevalidator $authActiveUserRevalidator,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $authenticated = $request->user() ?? Auth::guard('sanctum')->user();

        if (
            $authenticated instanceof User
            && ! $this->authActiveUserRevalidator->revalidate($authenticated)
        ) {
            throw new AuthenticationException;
        }

        /** @var Response $response */
        $response = $next($request);

        return $response;
    }
}
