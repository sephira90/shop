<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Cart\Commands\RemoveCartItemCommand;
use App\Application\Cart\Commands\RemoveCartItemHandler;
use App\Application\Cart\Commands\UpsertCartItemCommand;
use App\Application\Cart\Commands\UpsertCartItemHandler;
use App\Application\Cart\Dto\RemoveCartItemInputDto;
use App\Application\Cart\Queries\GetCurrentCartHandler;
use App\Application\Cart\Queries\GetCurrentCartQuery;
use App\Http\Controllers\Concerns\ResolvesAuthenticatedUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\UpsertCartItemRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
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
    public function removeItem(Request $request, int $variantId): JsonResponse
    {
        $guestToken = $request->query('guest_token', $request->header('X-Cart-Token'));
        $currentUser = $this->resolveAuthenticatedUser($request);
        $cartPayload = $this->removeCartItemHandler->handle(
            new RemoveCartItemCommand(
                $currentUser,
                RemoveCartItemInputDto::fromRaw($guestToken, $variantId),
            )
        );

        return ApiResponse::data($cartPayload->toArray());
    }
}
