<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Application\Auth\AuthApplicationException;
use App\Application\Auth\Commands\ResendAuthVerificationCommand;
use App\Application\Auth\Commands\ResendAuthVerificationHandler;
use App\Application\Auth\Commands\VerifyAuthEmailCommand;
use App\Application\Auth\Commands\VerifyAuthEmailHandler;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificationController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(
        private readonly VerifyAuthEmailHandler $verifyAuthEmailHandler,
        private readonly ResendAuthVerificationHandler $resendAuthVerificationHandler,
    ) {}

    /**
     * Mark user email as verified.
     */
    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return ApiResponse::error('Invalid verification link.', Response::HTTP_FORBIDDEN);
        }

        try {
            $message = $this->verifyAuthEmailHandler->handle(
                new VerifyAuthEmailCommand($id, $hash)
            );
        } catch (AuthApplicationException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->statusCode);
        }

        return ApiResponse::data([
            'message' => $message,
        ]);
    }

    /**
     * Resend verification notification.
     */
    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return ApiResponse::error('Authentication is required.', Response::HTTP_UNAUTHORIZED);
        }

        $message = $this->resendAuthVerificationHandler->handle(
            new ResendAuthVerificationCommand($user)
        );

        return ApiResponse::data([
            'message' => $message,
        ]);
    }
}
