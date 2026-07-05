<?php

declare(strict_types=1);

namespace App\Domains\Checkout\Controllers;

use App\Domains\Checkout\Application\Commands\InitiateCheckoutPaymentCommand;
use App\Domains\Checkout\Application\Commands\InitiateCheckoutPaymentHandler;
use App\Domains\Checkout\Application\Commands\PlaceCheckoutOrderCommand;
use App\Domains\Checkout\Application\Commands\PlaceCheckoutOrderHandler;
use App\Http\Controllers\Concerns\ResolvesAuthenticatedUser;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class CheckoutController extends Controller
{
    use ResolvesAuthenticatedUser;

    /**
     * Create controller instance.
     */
    public function __construct(
        private readonly PlaceCheckoutOrderHandler $placeCheckoutOrderHandler,
        private readonly InitiateCheckoutPaymentHandler $initiateCheckoutPaymentHandler,
    ) {}

    /**
     * Place order from active cart.
     */
    public function placeOrder(PlaceOrderRequest $request): JsonResponse
    {
        $input = $request->toDto();
        $currentUser = $this->resolveAuthenticatedUser($request);
        $command = new PlaceCheckoutOrderCommand($input, $request->idempotencyKey(), $currentUser);
        $result = $this->placeCheckoutOrderHandler->handle($command);

        return ApiResponse::data($result->toArray(), Response::HTTP_CREATED);
    }

    /**
     * Initiate payment for order.
     */
    public function pay(InitiatePaymentRequest $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $payment = $this->initiateCheckoutPaymentHandler->handle(
            new InitiateCheckoutPaymentCommand($order, $request->idempotencyKey())
        );

        return ApiResponse::data($payment->toArray());
    }
}
