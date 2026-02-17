<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\UpsertCartItemRequest;
use App\Services\Cart\CartService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $cart = $this->cartService->resolve($request->user(), is_string($guestToken) ? $guestToken : null);

        return response()->json([
            'data' => $this->cartService->payload($cart),
        ]);
    }

    /**
     * Add or update cart item.
     */
    public function upsertItem(UpsertCartItemRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $cart = $this->cartService->resolve($request->user(), $payload['guest_token'] ?? null);

        try {
            $cart = $this->cartService->upsertItem(
                $cart,
                (int) $payload['product_variant_id'],
                (int) $payload['quantity'],
            );
        } catch (DomainException $exception) {
            return response()->json([
                'error' => [
                    'message' => $exception->getMessage(),
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'data' => $this->cartService->payload($cart),
        ]);
    }

    /**
     * Remove cart item by variant id.
     */
    public function removeItem(Request $request, int $variantId): JsonResponse
    {
        $guestToken = $request->query('guest_token', $request->header('X-Cart-Token'));
        $cart = $this->cartService->resolve($request->user(), is_string($guestToken) ? $guestToken : null);
        $cart = $this->cartService->removeItem($cart, $variantId);

        return response()->json([
            'data' => $this->cartService->payload($cart),
        ]);
    }
}
