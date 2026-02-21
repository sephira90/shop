<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Support\Api\ApiResponse;
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

        return ApiResponse::data([
            'token' => $token,
            'user' => $this->userPayload($user),
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
            return ApiResponse::error('Invalid credentials.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $user->is_active) {
            return ApiResponse::error('User account is disabled.', Response::HTTP_FORBIDDEN);
        }

        if (! empty($payload['guest_token'])) {
            $this->cartService->mergeGuestCart($user, (string) $payload['guest_token']);
        }

        $token = $user->createToken((string) ($payload['device_name'] ?? 'api-device'))->plainTextToken;

        return ApiResponse::data([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Logout current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::data([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Return current user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $authenticated = $request->user();

        if (! $authenticated instanceof User) {
            return ApiResponse::error('Authentication is required.', Response::HTTP_UNAUTHORIZED);
        }

        return ApiResponse::data($this->userPayload($authenticated));
    }

    /**
     * Update current user profile fields.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $authenticated = $request->user();

        if (! $authenticated instanceof User) {
            return ApiResponse::error('Authentication is required.', Response::HTTP_UNAUTHORIZED);
        }

        $payload = $request->validated();
        $firstName = trim((string) $payload['first_name']);
        $lastName = trim((string) $payload['last_name']);
        $phone = isset($payload['phone']) ? trim((string) $payload['phone']) : '';

        $authenticated->update([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => trim($firstName.' '.$lastName),
            'phone' => $phone !== '' ? $phone : null,
        ]);

        return ApiResponse::data($this->userPayload($authenticated->fresh()));
    }

    /**
     * Build user payload for API responses.
     *
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_email_verified' => $user->hasVerifiedEmail(),
            'roles' => $user->roles()->pluck('name')->all(),
        ];
    }
}
