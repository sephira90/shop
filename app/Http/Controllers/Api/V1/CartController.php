<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\UpsertCartItemRequest;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Support\Api\ApiResponse;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CartController extends Controller
{
    /**
     * Create controller instance.
     */
    public function __construct(private readonly CartService $cartService) {}

    /**
     * Return current cart payload.
     */
    public function show(Request $request): JsonResponse
    {
        $guestToken = $request->query('guest_token', $request->header('X-Cart-Token'));
        $currentUser = $this->resolveCurrentUser($request);
        $cart = $this->cartService->resolve($currentUser, is_string($guestToken) ? $guestToken : null);

        return ApiResponse::data($this->cartService->payload($cart));
    }

    /**
     * Add or update cart item.
     */
    public function upsertItem(UpsertCartItemRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $currentUser = $this->resolveCurrentUser($request);
        $cart = $this->cartService->resolve($currentUser, $payload['guest_token'] ?? null);

        try {
            $cart = $this->cartService->upsertItem(
                $cart,
                (int) $payload['product_variant_id'],
                (int) $payload['quantity'],
            );
        } catch (DomainException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return ApiResponse::data($this->cartService->payload($cart));
    }

    /**
     * Remove cart item by variant id.
     */
    public function removeItem(Request $request, int $variantId): JsonResponse
    {
        $guestToken = $request->query('guest_token', $request->header('X-Cart-Token'));
        $currentUser = $this->resolveCurrentUser($request);
        $cart = $this->cartService->resolve($currentUser, is_string($guestToken) ? $guestToken : null);
        $cart = $this->cartService->removeItem($cart, $variantId);

        return ApiResponse::data($this->cartService->payload($cart));
    }

    /**
     * Resolve currently authenticated user if it is app User model.
     */
    private function resolveCurrentUser(Request $request): ?User
    {
        $authenticated = $request->user() ?? Auth::guard('sanctum')->user();

        return $authenticated instanceof User ? $authenticated : null;
    }
}
