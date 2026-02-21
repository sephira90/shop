<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificationController extends Controller
{
    /**
     * Mark user email as verified.
     */
    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return ApiResponse::error('Invalid verification link.', Response::HTTP_FORBIDDEN);
        }

        $user = User::query()->find($id);
        if (! $user instanceof User) {
            return ApiResponse::error('User not found.', Response::HTTP_NOT_FOUND);
        }

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return ApiResponse::error('Invalid verification hash.', Response::HTTP_FORBIDDEN);
        }

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::data([
                'message' => 'Email already verified.',
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return ApiResponse::data([
            'message' => 'Email verified successfully.',
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

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::data([
                'message' => 'Email already verified.',
            ]);
        }

        $user->sendEmailVerificationNotification();

        return ApiResponse::data([
            'message' => 'Verification email has been sent.',
        ]);
    }
}
