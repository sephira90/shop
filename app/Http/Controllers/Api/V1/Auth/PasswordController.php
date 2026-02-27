<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Application\Auth\AuthApplicationException;
use App\Application\Auth\Commands\ForgotAuthPasswordCommand;
use App\Application\Auth\Commands\ForgotAuthPasswordHandler;
use App\Application\Auth\Commands\ResetAuthPasswordCommand;
use App\Application\Auth\Commands\ResetAuthPasswordHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(
        private readonly ForgotAuthPasswordHandler $forgotAuthPasswordHandler,
        private readonly ResetAuthPasswordHandler $resetAuthPasswordHandler,
    ) {}

    /**
     * Send password reset link.
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $message = $this->forgotAuthPasswordHandler->handle(
                new ForgotAuthPasswordCommand($request->toDto())
            );
        } catch (AuthApplicationException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->statusCode);
        }

        return ApiResponse::data([
            'message' => $message,
        ]);
    }

    /**
     * Reset user password with token.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $message = $this->resetAuthPasswordHandler->handle(
                new ResetAuthPasswordCommand($request->toDto())
            );
        } catch (AuthApplicationException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->statusCode);
        }

        return ApiResponse::data([
            'message' => $message,
        ]);
    }
}
