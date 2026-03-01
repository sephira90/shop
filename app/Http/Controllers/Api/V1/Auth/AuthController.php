<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Application\Auth\AuthApplicationException;
use App\Application\Auth\Commands\LoginAuthUserCommand;
use App\Application\Auth\Commands\LoginAuthUserHandler;
use App\Application\Auth\Commands\LogoutAuthUserCommand;
use App\Application\Auth\Commands\LogoutAuthUserHandler;
use App\Application\Auth\Commands\RegisterAuthUserCommand;
use App\Application\Auth\Commands\RegisterAuthUserHandler;
use App\Application\Auth\Commands\UpdateAuthProfileCommand;
use App\Application\Auth\Commands\UpdateAuthProfileHandler;
use App\Application\Auth\Queries\GetAuthProfileHandler;
use App\Application\Auth\Queries\GetAuthProfileQuery;
use App\Http\Controllers\Concerns\ResolvesAuthenticatedUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Support\Api\ApiResponse;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    use ResolvesAuthenticatedUser;

    /**
     * Create controller instance.
     */
    public function __construct(
        private readonly RegisterAuthUserHandler $registerAuthUserHandler,
        private readonly LoginAuthUserHandler $loginAuthUserHandler,
        private readonly LogoutAuthUserHandler $logoutAuthUserHandler,
        private readonly GetAuthProfileHandler $getAuthProfileHandler,
        private readonly UpdateAuthProfileHandler $updateAuthProfileHandler,
    ) {}

    /**
     * Register new customer account.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->registerAuthUserHandler->handle(
            new RegisterAuthUserCommand($request->toDto())
        );

        return ApiResponse::data($result->toArray(), Response::HTTP_CREATED);
    }

    /**
     * Authenticate user and issue token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->loginAuthUserHandler->handle(
                new LoginAuthUserCommand($request->toDto())
            );
        } catch (AuthApplicationException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->statusCode);
        } catch (DomainException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return ApiResponse::data($result->toArray());
    }

    /**
     * Logout current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $authenticated = $this->requireAuthenticatedUser($request);
        $this->logoutAuthUserHandler->handle(new LogoutAuthUserCommand($authenticated));

        return ApiResponse::data([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Return current user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $authenticated = $this->requireAuthenticatedUser($request);

        return ApiResponse::data(
            $this->getAuthProfileHandler->handle(new GetAuthProfileQuery($authenticated))->toArray()
        );
    }

    /**
     * Update current user profile fields.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $authenticated = $this->requireAuthenticatedUser($request);

        return ApiResponse::data(
            $this->updateAuthProfileHandler->handle(
                new UpdateAuthProfileCommand($authenticated, $request->toDto())
            )->toArray()
        );
    }
}
