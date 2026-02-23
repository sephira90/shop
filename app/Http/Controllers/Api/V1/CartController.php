<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Cart\Commands\RemoveCartItemCommand;
use App\Application\Cart\Commands\RemoveCartItemHandler;
use App\Application\Cart\Commands\UpsertCartItemCommand;
use App\Application\Cart\Commands\UpsertCartItemHandler;
use App\Application\Cart\Queries\GetCurrentCartHandler;
use App\Application\Cart\Queries\GetCurrentCartQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\UpsertCartItemRequest;
use App\Models\User;
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
    public function __construct(
        private readonly GetCurrentCartHandler $getCurrentCartHandler,
        private readonly UpsertCartItemHandler $upsertCartItemHandler,
        private readonly RemoveCartItemHandler $removeCartItemHandler,
    ) {}

    /**
     * Return current cart payload.
     */
    public function show(Request $request): JsonResponse
    {
        $guestToken = $request->query('guest_token', $request->header('X-Cart-Token'));
        $currentUser = $this->resolveCurrentUser($request);
        $payload = $this->getCurrentCartHandler->handle(
            new GetCurrentCartQuery($currentUser, is_string($guestToken) ? $guestToken : null)
        );

        return ApiResponse::data($payload);
    }

    /**
     * Add or update cart item.
     */
    public function upsertItem(UpsertCartItemRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $currentUser = $this->resolveCurrentUser($request);

        try {
            $cartPayload = $this->upsertCartItemHandler->handle(
                new UpsertCartItemCommand(
                    $currentUser,
                    $payload['guest_token'] ?? null,
                    (int) $payload['product_variant_id'],
                    (int) $payload['quantity'],
                )
            );
        } catch (DomainException $exception) {
            return ApiResponse::error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return ApiResponse::data($cartPayload);
    }

    /**
     * Remove cart item by variant id.
     */
    public function removeItem(Request $request, int $variantId): JsonResponse
    {
        $guestToken = $request->query('guest_token', $request->header('X-Cart-Token'));
        $currentUser = $this->resolveCurrentUser($request);
        $cartPayload = $this->removeCartItemHandler->handle(
            new RemoveCartItemCommand($currentUser, is_string($guestToken) ? $guestToken : null, $variantId)
        );

        return ApiResponse::data($cartPayload);
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
