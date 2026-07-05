<?php

declare(strict_types=1);

namespace App\Domains\Cart\Controllers;

use App\Domains\Cart\Application\Commands\RemoveCartItemCommand;
use App\Domains\Cart\Application\Commands\RemoveCartItemHandler;
use App\Domains\Cart\Application\Commands\UpsertCartItemCommand;
use App\Domains\Cart\Application\Commands\UpsertCartItemHandler;
use App\Domains\Cart\Application\Queries\GetCurrentCartHandler;
use App\Domains\Cart\Application\Queries\GetCurrentCartQuery;
use App\Http\Controllers\Concerns\ResolvesAuthenticatedUser;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CartController extends Controller
{
    use ResolvesAuthenticatedUser;

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
        $this->authorize('viewAny', Cart::class);

        $guestToken = $request->query('guest_token', $request->header('X-Cart-Token'));
        $currentUser = $this->resolveAuthenticatedUser($request);
        $payload = $this->getCurrentCartHandler->handle(
            new GetCurrentCartQuery($currentUser, is_string($guestToken) ? $guestToken : null)
        );

        return ApiResponse::data($payload->toArray());
    }

    /**
     * Add or update cart item.
     */
    public function upsertItem(UpsertCartItemRequest $request): JsonResponse
    {
        $input = $request->toDto();
        $currentUser = $this->resolveAuthenticatedUser($request);
        $this->authorize('modify', [Cart::class, $input->guestToken]);
        $cartPayload = $this->upsertCartItemHandler->handle(
            new UpsertCartItemCommand(
                $currentUser,
                $input,
            )
        );

        return ApiResponse::data($cartPayload->toArray());
    }

    /**
     * Remove cart item by variant id.
     */
    public function removeItem(RemoveCartItemRequest $request): JsonResponse
    {
        $input = $request->toDto();
        $currentUser = $this->resolveAuthenticatedUser($request);
        $this->authorize('modify', [Cart::class, $input->guestToken]);
        $cartPayload = $this->removeCartItemHandler->handle(
            new RemoveCartItemCommand(
                $currentUser,
                $input,
            )
        );

        return ApiResponse::data($cartPayload->toArray());
    }
}
