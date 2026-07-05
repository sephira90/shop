<?php

declare(strict_types=1);

namespace App\Domains\Users\Controllers;

use App\Domains\Users\Application\AuthApplicationException;
use App\Domains\Users\Application\Commands\ForgotAuthPasswordCommand;
use App\Domains\Users\Application\Commands\ForgotAuthPasswordHandler;
use App\Domains\Users\Application\Commands\ResetAuthPasswordCommand;
use App\Domains\Users\Application\Commands\ResetAuthPasswordHandler;
use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class PasswordController extends Controller
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
            return ApiResponse::error(
                $exception->getMessage(),
                $exception->statusCode,
                ['type' => class_basename($exception)],
            );
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
            return ApiResponse::error(
                $exception->getMessage(),
                $exception->statusCode,
                ['type' => class_basename($exception)],
            );
        }

        return ApiResponse::data([
            'message' => $message,
        ]);
    }
}
