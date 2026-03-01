<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

trait ResolvesAuthenticatedUser
{
    protected function resolveAuthenticatedUser(Request $request): ?User
    {
        $authenticated = $request->user() ?? Auth::guard('sanctum')->user();

        return $authenticated instanceof User ? $authenticated : null;
    }

    protected function requireAuthenticatedUser(Request $request): User
    {
        $authenticated = $this->resolveAuthenticatedUser($request);

        if ($authenticated instanceof User) {
            return $authenticated;
        }

        throw new HttpResponseException(
            ApiResponse::error('Authentication is required.', Response::HTTP_UNAUTHORIZED)
        );
    }
}
