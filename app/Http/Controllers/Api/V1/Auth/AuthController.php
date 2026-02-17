<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(private readonly CartService $cartService) {}

    /**
     * Register new customer account.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $user = User::query()->create([
            'first_name' => $payload['first_name'],
            'last_name' => $payload['last_name'],
            'name' => trim($payload['first_name'].' '.$payload['last_name']),
            'email' => $payload['email'],
            'phone' => $payload['phone'] ?? null,
            'password' => $payload['password'],
        ]);

        $user->assignRole(RoleName::CUSTOMER);
        $user->sendEmailVerificationNotification();

        $token = $user->createToken('api-register')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles()->pluck('name')->all(),
                ],
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Authenticate user and issue token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $user = User::query()->where('email', $payload['email'])->first();

        if (! $user instanceof User || ! Hash::check($payload['password'], $user->password)) {
            return response()->json([
                'error' => [
                    'message' => 'Invalid credentials.',
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $user->is_active) {
            return response()->json([
                'error' => [
                    'message' => 'User account is disabled.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        if (! empty($payload['guest_token'])) {
            $this->cartService->mergeGuestCart($user, (string) $payload['guest_token']);
        }

        $token = $user->createToken((string) ($payload['device_name'] ?? 'api-device'))->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_email_verified' => $user->hasVerifiedEmail(),
                    'roles' => $user->roles()->pluck('name')->all(),
                ],
            ],
        ]);
    }

    /**
     * Logout current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'data' => [
                'message' => 'Logged out successfully.',
            ],
        ]);
    }

    /**
     * Return current user profile.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_email_verified' => $user->hasVerifiedEmail(),
                'roles' => $user->roles()->pluck('name')->all(),
            ],
        ]);
    }
}
